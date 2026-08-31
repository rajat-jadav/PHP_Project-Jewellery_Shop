<?php
require_once __DIR__ . '/config/config.php';
require_login();
$pageTitle = 'Book Appointment';

$productId = (int) ($_GET['product_id'] ?? $_POST['product_id'] ?? 0);
$stmt = $pdo->prepare("SELECT p.*, b.name AS branch_name, b.business_hours FROM products p JOIN branches b ON p.branch_id = b.id WHERE p.id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    flash('error', 'Product not found.');
    redirect('/products.php');
}

if ($product['status'] !== 'available') {
    flash('error', 'Sorry, this product is no longer available for appointments.');
    redirect('/product-details.php?id=' . $productId);
}

// Fixed hourly slots (matches typical 10am - 8pm showroom hours)
$allSlots = ['10:00 AM', '11:00 AM', '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM', '06:00 PM', '07:00 PM'];

$selectedDate = $_POST['appointment_date'] ?? date('Y-m-d', strtotime('+1 day'));

// Find already-booked slots for this branch+date (any product) so two customers don't collide
$bookedStmt = $pdo->prepare("SELECT time_slot FROM appointments WHERE branch_id = ? AND appointment_date = ? AND status IN ('pending','approved','rescheduled')");
$bookedStmt->execute([$product['branch_id'], $selectedDate]);
$bookedSlots = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);
$availableSlots = array_diff($allSlots, $bookedSlots);

/* Handle booking submission */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $date = $_POST['appointment_date'];
    $slot = $_POST['time_slot'];
    $purpose = clean($_POST['purpose']);

    // re-check product is still available and slot still free (avoid race condition)
    $checkProduct = $pdo->prepare("SELECT status FROM products WHERE id = ?");
    $checkProduct->execute([$productId]);
    $stillAvailable = $checkProduct->fetchColumn() === 'available';

    $checkSlot = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE branch_id = ? AND appointment_date = ? AND time_slot = ? AND status IN ('pending','approved','rescheduled')");
    $checkSlot->execute([$product['branch_id'], $date, $slot]);
    $slotTaken = $checkSlot->fetchColumn() > 0;

    if (!$stillAvailable) {
        flash('error', 'This product was just booked by someone else. Please browse similar items.');
        redirect('/product-details.php?id=' . $productId);
    } elseif ($slotTaken) {
        flash('error', 'That time slot was just taken. Please pick another.');
        redirect('/appointment.php?product_id=' . $productId);
    } else {
        $ins = $pdo->prepare("INSERT INTO appointments (user_id, product_id, branch_id, appointment_date, time_slot, purpose, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $ins->execute([current_user_id(), $productId, $product['branch_id'], $date, $slot, $purpose]);

        // NOTE: Hook email/WhatsApp notification here (PHPMailer / WhatsApp API).
        // notify_user_email($userEmail, "Appointment requested for {$product['name']} on $date at $slot");

        flash('success', 'Appointment requested! You will be notified once the store confirms it.');
        redirect('/profile.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card p-4">
      <h4>Book Appointment</h4>
      <p class="text-muted mb-1"><?= clean($product['name']) ?> &bull; ₹<?= number_format($product['final_price'], 2) ?></p>
      <p class="text-muted small">Showroom: <?= clean($product['branch_name']) ?> (<?= clean($product['business_hours']) ?>)</p>

      <form method="post" id="apptForm">
        <input type="hidden" name="product_id" value="<?= $productId ?>">

        <label class="form-label">Date</label>
        <input type="date" name="appointment_date" class="form-control mb-3" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" value="<?= clean($selectedDate) ?>" onchange="this.form.submit()" required>

        <label class="form-label">Time Slot</label>
        <select name="time_slot" class="form-select mb-3" required>
          <?php if (empty($availableSlots)): ?>
            <option value="">No slots available this day</option>
          <?php else: foreach ($availableSlots as $slot): ?>
            <option value="<?= $slot ?>"><?= $slot ?></option>
          <?php endforeach; endif; ?>
        </select>

        <label class="form-label">Purpose</label>
        <select name="purpose" class="form-select mb-3">
          <option>View & Purchase</option>
          <option>View Only</option>
          <option>Resizing / Alteration</option>
          <option>Certificate Query</option>
        </select>

        <button type="submit" name="book_appointment" class="btn btn-dark w-100" <?= empty($availableSlots) ? 'disabled' : '' ?>>Confirm Appointment</button>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
