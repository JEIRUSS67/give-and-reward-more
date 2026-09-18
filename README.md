# Give and Reward More — Website

A frontend (HTML5 / CSS3 / vanilla JavaScript) and backend (PHP / MySQL)
website for Give and Reward More, an organization in Uganda supporting
children with shelter, food, clothing, healthcare, education, and other
essential needs.

This build follows the project brief closely: no frontend frameworks, no
emojis anywhere, mobile-first responsive design, and careful handling of
child privacy — all photos are placeholders until Give and Reward More
authorizes real images, and no financial/payment details, statistics, or
organizational history have been invented.

## What's included

- **Frontend pages:** `index.html`, `about.html`, `impact.html`,
  `needs.html`, `ways-to-help.html`, `stories.html`, `gallery.html`,
  `contact.html`
- **Styling:** `css/style.css` (design system, mobile-first base),
  `css/responsive.css` (tablet/desktop breakpoints), `css/admin.css`
  (admin dashboard only)
- **Scripts:** `js/main.js` (nav, scroll reveal, stat counters),
  `js/gallery.js` (lightbox), `js/forms.js` (client-side validation +
  submission for the contact and support-request forms)
- **Backend:** `php/` — config, database connection, shared helpers,
  the contact and support-request form processors, and admin
  authentication
- **Admin dashboard:** `admin/` — login, overview, message/request
  management, and content management for "What We Need" and
  "Stories & Updates"
- **Database:** `database/database.sql` — full MySQL schema

## Local / server setup

1. **Create the database.**
   ```
   mysql -u root -p < database/database.sql
   ```

2. **Set configuration via environment variables** (never hardcode
   credentials in `php/config.php`):
   ```
   GARM_DB_HOST=127.0.0.1
   GARM_DB_NAME=give_and_reward_more
   GARM_DB_USER=your_db_user
   GARM_DB_PASS=your_db_password
   GARM_SITE_URL=https://your-domain.example
   GARM_ENV=production
   ```
   How you set these depends on your host: an Apache `SetEnv` directive,
   an `.env` loader, or your hosting panel's environment variables screen.

3. **Create the first admin account** (CLI only — there is no public
   sign-up form, by design):
   ```
   php php/create_admin.php "Full Name" "admin@example.org"
   ```
   You'll be prompted for a password, which is hashed with PHP's
   `password_hash()` before being stored.

4. **Point your web server's document root at this folder** (Apache or
   Nginx with PHP-FPM). The included root `.htaccess` disables directory
   listing, blocks direct access to `database.sql` and internal PHP
   includes, and sets baseline security headers.

5. **Enable HTTPS** before going live, then uncomment the HTTPS redirect
   block in the root `.htaccess`.

## Content still needed from Give and Reward More

See the project proposal for the full list, but at minimum the site
currently has placeholders for: the logo, real photography (with
documented consent for any image of a child), verified impact
statistics, confirmed "What We Need" priorities, confirmed ways-to-help
categories, verified payment/donation details, and contact details
(phone, email, address, WhatsApp, social media, map).

## Notes on the placeholder images

`images/*.svg` are labeled placeholder graphics generated for this
build so every page has visual weight without fabricating photography.
Replace them with authorized photographs as they become available —
file names are self-descriptive (e.g. `impact-education.svg`).
