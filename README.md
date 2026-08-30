# Mahavir Ornaments

A jewellery e-commerce site built in PHP and MySQL, with a live virtual try-on feature — point your camera at your hand or face and see rings, bracelets, necklaces, or earrings placed on you in real time, right in the browser.

## Live Project Link
[Mahavir Ornaments](//rajat.infinityfree.me/)

## What's in here

**Storefront**
- Product catalogue with search, filters, and a "try-on available only" toggle
- Product detail pages with materials, gemstones, and weight
- Compare up to 3 products side by side
- Wishlist and cart
- Certificate verification — look up a piece by its certificate number, product name, or SKU and see its authenticity, materials, and gemstone details
- In-store appointment booking, with time slots that lock once someone else books them for that branch and date
- User accounts: register, login, password reset, profile

**Virtual try-on** (`tryon.php` + `assets/js/tryon.js`)
- Runs entirely client-side using [MediaPipe Tasks Vision](https://developers.google.com/mediapipe) — `HandLandmarker` for rings/bracelets, `FaceLandmarker` for necklaces/earrings
- Two modes: live camera or an uploaded photo
- For rings, you can pick which finger to try it on (thumb through pinky) — the overlay repositions using that finger's actual landmark points
- Camera can be switched between front/back
- No server-side processing at all — the model itself downloads and runs in the visitor's browser

**Admin panel** (`admin/`)
- Dashboard, activity logs, reports
- Products, categories, collections, branches
- Purchases, users, reviews, contact messages
- Appointments
- Admin accounts and site settings

## Stack

- PHP (PDO, prepared statements throughout)
- MySQL
- Bootstrap 5 + vanilla JS on the frontend
- MediaPipe Tasks Vision for the try-on (loaded from CDN, no install needed)

## Database

`database/schema.sql` has the full schema — 17 tables, including:

`users`, `admins`, `products`, `product_images`, `product_materials`, `product_gemstones`, `categories`, `collections`, `branches`, `certificates`, `appointments`, `wishlist`, `reviews`, `purchases`, `contact_messages`, `site_settings`, `activity_logs`

## A note on the try-on and deployment

Because the try-on runs in the browser, it doesn't need anything special on the server — no Python, no GPU, no ML runtime. The one hard requirement is **HTTPS**: browsers block camera access (`getUserMedia`) on plain HTTP except on `localhost`, so any live host needs SSL for try-on to work at all.
