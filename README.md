# Aurum & Ember Restaurant Website

Red & gold fine dining website for Pretoria, South Africa.

## Files

| File | Purpose |
|------|---------|
| `index.html` | Full restaurant website (menu, specials, events, reservation form) |
| `reserve.php` | Handles reservation form submissions |
| `reservations.json` | Auto-created log of all bookings (do not delete) |

## Features

- 🍽️ Full interactive menu (Starters, Mains, Sides, Desserts, Drinks)
- ⭐ Today's Chef Specials section
- 📅 Upcoming Events calendar
- 📋 Reservation form with PHP backend
- 📧 Email confirmation to guest + alert to restaurant
- 📁 JSON log of all reservations
- 📱 Mobile responsive

## Deployment

1. Upload both files to your PHP web server (cPanel, Hostinger, etc.)
2. Open `reserve.php` and update:
   - `RESTAURANT_EMAIL` → your bookings email
   - `TIMEZONE` → already set to Africa/Johannesburg
3. Make sure `mail()` is enabled on your server (or configure SMTP)
4. Ensure the server can write `reservations.json` (chmod 664 if needed)

## Customisation

- **Restaurant name/address**: Search for "Aurum & Ember" and "Church Street" in `index.html`
- **Phone number**: Search for "+27 12 345 6789"
- **Menu items & prices**: Edit the menu items directly in `index.html`
- **Events**: Update the events section in `index.html`
- **Colors**: Change CSS variables at the top of `index.html` (`:root {}`)

## Viewing Reservations

Open `reservations.json` in any text editor or JSON viewer to see all bookings.

## Requirements

- PHP 7.4+
- `mail()` enabled (or SMTP configured)
- Write permissions for `reservations.json`
