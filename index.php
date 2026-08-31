<?php
$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';

$featured = $pdo->query("SELECT * FROM products WHERE status = 'available' ORDER BY created_at DESC LIMIT 8")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active'")->fetchAll();
$branches = $pdo->query("SELECT * FROM branches WHERE status = 'active'")->fetchAll();

// Editorial fallback images for the 4 marquee categories
$catImages = [
  'rings'     => BASE_URL . '/assets/img/cat-rings.jpg',
  'necklaces' => BASE_URL . '/assets/img/cat-necklaces.jpg',
  'earrings'  => BASE_URL . '/assets/img/cat-earrings.jpg',
  'bangles'   => BASE_URL . '/assets/img/cat-bangles.jpg',
];
function cat_image($name, $catImages) {
  $k = strtolower(trim($name));
  // Check longer/more specific keywords first so "Earrings" doesn't
  // accidentally match the "rings" keyword (it contains that substring),
  // and map "Bracelets" to the bangles photo since that's the closest
  // asset we have on hand.
  $checks = [
    'earring'  => $catImages['earrings'],
    'necklace' => $catImages['necklaces'],
    'bracelet' => $catImages['bangles'],
    'bangle'   => $catImages['bangles'],
    'ring'     => $catImages['rings'],
  ];
  foreach ($checks as $needle => $url) {
    if (strpos($k, $needle) !== false) return $url;
  }
  return $catImages['necklaces'];
}
?>

<!-- ==================== HERO ==================== -->
<section class="hero-editorial">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-md-6">
        <p class="eyebrow">Maison of Aurum · Est. 1974</p>
        <h1 class="mt-3">Jewellery that <span class="italic-serif text-gold-gradient">outlives</span><br>the moment it was made for.</h1>
        <p class="lead mt-4">Hand-finished in our Mumbai atelier. Certified diamonds, ethically-sourced gold, and the quiet confidence of pieces made to be worn every day — and passed on.</p>
        <div class="d-flex flex-wrap gap-3 mt-4">
          <a href="<?= BASE_URL ?>/products.php" class="btn btn-gold btn-lg">Explore collections <i class="bi bi-arrow-right"></i></a>
          <a href="<?= BASE_URL ?>/products.php" class="btn btn-outline-light btn-lg"><i class="bi bi-stars"></i> Try on virtually</a>
        </div>
        <div class="hero-stats">
          <div><div class="num">50+</div><div class="lbl">Years of craft</div></div>
          <div><div class="num">12k</div><div class="lbl">Heirlooms placed</div></div>
          <div><div class="num">IGI</div><div class="lbl">Certified</div></div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="hero-img-wrap">
          <img src="<?= BASE_URL ?>/assets/img/hero-jewel.jpg" alt="Diamond necklace on emerald marble">
          <div class="hero-img-card">
            <div class="eyebrow" style="color:var(--sand)">Piece n° 018</div>
            <div class="name mt-1">Verde Solitaire</div>
            <div class="meta">1.24 ct · 18k yellow gold</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== TRUST STRIP ==================== -->
<section class="trust-strip">
  <div class="container">
    <div class="item"><i class="bi bi-patch-check"></i> BIS Hallmarked</div>
    <div class="item"><i class="bi bi-gem"></i> IGI · GIA Certified</div>
    <div class="item"><i class="bi bi-stars"></i> Lifetime buy-back</div>
    <div class="item"><i class="bi bi-calendar-event"></i> Private appointments</div>
  </div>
</section>

<!-- ==================== CATEGORIES ==================== -->
<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <p class="eyebrow">Shop by category</p>
        <h2>The four houses</h2>
      </div>
      <a class="link d-none d-md-inline" href="<?= BASE_URL ?>/products.php">View all →</a>
    </div>
    <div class="cat-grid">
      <?php if (empty($categories)):
        $fallback = [['name'=>'Rings'],['name'=>'Necklaces'],['name'=>'Earrings'],['name'=>'Bangles']];
        foreach ($fallback as $cat): ?>
          <a class="cat-tile" href="<?= BASE_URL ?>/products.php">
            <img src="<?= cat_image($cat['name'], $catImages) ?>" alt="<?= clean($cat['name']) ?>">
            <div class="cap"><div class="name"><?= clean($cat['name']) ?></div><div class="count">Explore →</div></div>
          </a>
      <?php endforeach;
      else: foreach (array_slice($categories, 0, 4) as $cat): ?>
        <a class="cat-tile" href="<?= BASE_URL ?>/products.php?category=<?= $cat['id'] ?>">
          <img src="<?= cat_image($cat['name'], $catImages) ?>" alt="<?= clean($cat['name']) ?>">
          <div class="cap"><div class="name"><?= clean($cat['name']) ?></div><div class="count">Explore →</div></div>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<!-- ==================== FEATURED PRODUCTS ==================== -->
<section class="section section-cream">
  <div class="container">
    <div class="section-head">
      <div>
        <p class="eyebrow">Featured this season</p>
        <h2>New in the atelier</h2>
      </div>
      <a class="link d-none d-md-inline" href="<?= BASE_URL ?>/products.php">All pieces →</a>
    </div>
    <div class="row g-4">
      <?php if (empty($featured)):
        $demo = [
          ['name'=>'Verde Solitaire Pendant','sku'=>'AU-N-018','final_price'=>82400,'img'=>'cat-necklaces.jpg','tag'=>'New'],
          ['name'=>'Aurelia Cushion Ring','sku'=>'AU-R-042','final_price'=>124900,'img'=>'cat-rings.jpg','tag'=>'Bestseller'],
          ['name'=>'Lumière Drop Earrings','sku'=>'AU-E-011','final_price'=>46200,'img'=>'cat-earrings.jpg','tag'=>''],
          ['name'=>'Heritage Bangle Stack','sku'=>'AU-B-007','final_price'=>218000,'img'=>'cat-bangles.jpg','tag'=>'Limited'],
        ];
        foreach ($demo as $p): ?>
          <div class="col-6 col-md-3">
            <article class="product-card">
              <div class="img-wrap">
                <img src="<?= BASE_URL ?>/assets/img/<?= $p['img'] ?>" alt="<?= clean($p['name']) ?>">
                <?php if ($p['tag']): ?><span class="tag"><?= $p['tag'] ?></span><?php endif; ?>
                <div class="quick">Quick view</div>
              </div>
              <div class="card-body d-flex justify-content-between align-items-start gap-2">
                <div>
                  <h3 class="p-name mb-0"><?= clean($p['name']) ?></h3>
                  <div class="p-sku mt-1"><?= clean($p['sku']) ?></div>
                </div>
                <div class="p-price">₹<?= number_format($p['final_price']) ?></div>
              </div>
            </article>
          </div>
      <?php endforeach;
      else: foreach ($featured as $p): ?>
        <div class="col-6 col-md-3">
          <article class="product-card">
            <a href="<?= BASE_URL ?>/product-details.php?id=<?= $p['id'] ?>" class="text-reset">
              <div class="img-wrap">
                <img src="<?= $p['thumbnail'] ? UPLOAD_URL . '/products/' . clean($p['thumbnail']) : BASE_URL . '/assets/img/cat-necklaces.jpg' ?>" alt="<?= clean($p['name']) ?>">
                <div class="quick">Quick view</div>
              </div>
              <div class="card-body d-flex justify-content-between align-items-start gap-2">
                <div>
                  <h3 class="p-name mb-0"><?= clean($p['name']) ?></h3>
                  <div class="p-sku mt-1"><?= clean($p['sku']) ?></div>
                </div>
                <div class="p-price">₹<?= number_format($p['final_price'], 0) ?></div>
              </div>
            </a>
          </article>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<!-- ==================== VIRTUAL TRY-ON ==================== -->
<section class="section tryon-band">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-md-6 order-2 order-md-1">
        <p class="eyebrow" style="color:var(--sand)">Aurum studio</p>
        <h2 class="mt-3">See it on you,<br>before it's yours.</h2>
        <p class="lead mt-3">Our AR try-on lets you preview earrings, necklaces and rings in real time. Save your favourites, share with family, and book a fitting when you're ready.</p>
        <ul>
          <li>Live preview in your camera</li>
          <li>3D models of every piece</li>
          <li>Share look-books via link</li>
        </ul>
        <a href="<?= BASE_URL ?>/products.php" class="btn btn-gold mt-4">Launch try-on <i class="bi bi-stars"></i></a>
      </div>
      <div class="col-md-6 order-1 order-md-2">
        <div class="tryon-img">
          <img src="<?= BASE_URL ?>/assets/img/tryon.jpg" alt="Virtual try-on preview">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ==================== TESTIMONIALS ==================== -->
<section class="section">
  <div class="container">
    <div class="row g-4">
      <?php
      $testimonials = [
        ['q'=>'The Verde pendant is the piece I never take off. Craftsmanship you can feel.','a'=>'Ananya R.','c'=>'Mumbai'],
        ['q'=>'Booked a private appointment for our anniversary — the team designed a ring in six weeks.','a'=>'Rohan & Meera','c'=>'Bengaluru'],
        ['q'=>'Try-on made choosing bangles for my mother effortless. Delivery was pristine.','a'=>'Kabir S.','c'=>'Delhi'],
      ];
      foreach ($testimonials as $t): ?>
        <div class="col-md-4">
          <figure class="testimonial">
            <div class="stars"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i></div>
            <blockquote>"<?= $t['q'] ?>"</blockquote>
            <figcaption><span class="who"><?= $t['a'] ?></span> · <?= $t['c'] ?></figcaption>
          </figure>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ==================== SHOWROOMS ==================== -->
<section class="section section-cream">
  <div class="container">
    <div class="section-head">
      <div>
        <p class="eyebrow">Our showrooms</p>
        <h2>Visit us in person</h2>
      </div>
      <a href="<?= BASE_URL ?>/appointment.php" class="btn btn-outline-dark d-none d-md-inline-flex">Book appointment</a>
    </div>
    <div class="row g-4">
      <?php if (empty($branches)):
        $demoBranches = [
          ['name'=>'Aurum · South Mumbai','address'=>'Ground Floor, Kala Ghoda, Fort','phone'=>'+91 22 4000 1200','business_hours'=>'Mon–Sat · 11am – 8pm'],
          ['name'=>'Aurum · Bengaluru','address'=>'UB City Mall, Vittal Mallya Road','phone'=>'+91 80 4000 1200','business_hours'=>'Mon–Sun · 11am – 9pm'],
          ['name'=>'Aurum · Delhi','address'=>'DLF Emporio, Vasant Kunj','phone'=>'+91 11 4000 1200','business_hours'=>'Mon–Sun · 11am – 9pm'],
        ];
        foreach ($demoBranches as $b): ?>
          <div class="col-md-4">
            <div class="showroom">
              <div class="hairline-gold"></div>
              <h3><?= $b['name'] ?></h3>
              <ul>
                <li><i class="bi bi-geo-alt"></i> <?= $b['address'] ?></li>
                <li><i class="bi bi-telephone"></i> <?= $b['phone'] ?></li>
                <li><i class="bi bi-clock"></i> <?= $b['business_hours'] ?></li>
              </ul>
            </div>
          </div>
      <?php endforeach;
      else: foreach ($branches as $b): ?>
        <div class="col-md-4">
          <div class="showroom">
            <div class="hairline-gold"></div>
            <h3><?= clean($b['name']) ?></h3>
            <ul>
              <li><i class="bi bi-geo-alt"></i> <?= clean($b['address']) ?></li>
              <li><i class="bi bi-telephone"></i> <?= clean($b['phone']) ?></li>
              <li><i class="bi bi-clock"></i> <?= clean($b['business_hours']) ?></li>
            </ul>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>
</section>

<!-- ==================== NEWSLETTER CTA ==================== -->
<section class="cta-band section">
  <div class="container">
    <p class="eyebrow" style="color:var(--sand)">The Aurum letter</p>
    <h2 class="mt-3">Private previews, before anyone else.</h2>
    <p class="lead">One considered email a month — new collections, atelier stories, and invitations to private viewings.</p>
    <form onsubmit="event.preventDefault(); this.querySelector('button').innerText='Subscribed ✓';">
      <input type="email" required placeholder="your@email.com">
      <button type="submit" class="btn btn-gold">Subscribe</button>
    </form>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
