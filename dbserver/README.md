# BengalBet REST API & MySQL Database Server

**Deployment Target URL:** `https://deserver.neexthub.site/dbserver/`

## 📁 Directory Structure
```
dbserver/
├── .htaccess                 <- Apache URL rewriting
├── config.php                <- MySQL credentials, master secret key, CORS headers
├── database.sql              <- Complete MySQL schema & starter data dump
├── db.php                    <- PDO helper class with self-healing tables
├── index.php                 <- Central API gateway & router (/api/*)
├── masterapi.php             <- Master remote files & database controller
└── api/
    ├── auth.php              <- Authentication (Login, Register, Profiles)
    ├── wallet.php            <- Balances, Deposits, Withdrawals, Payment Cards
    ├── games.php             <- Bets, Game records, Profit & Loss
    ├── rewards.php           <- Lucky Spins, Golden Eggs, Red Envelopes
    ├── messages.php          <- Inbox notifications & Claimable bonuses
    ├── feedback.php          <- Customer support tickets
    ├── chat.php              <- Live Chat 24/7
    ├── settings.php          <- Payment channels, site notices, limits
    ├── referral.php          <- Multi-tier affiliate rewards & commissions
    ├── vip.php               <- VIP tiers & privilege bonuses
    ├── daily_signin.php      <- Daily attendance rewards
    ├── rescue.php            <- Loss rescue cashback fund
    ├── promos.php            <- Promotional gift codes
    ├── tickets.php           <- Lottery & voucher tickets
    ├── mystery_reward.php    <- 23rd Monthly Mystery Cash Card (৳1K - ৳50K)
    └── admin.php             <- Complete Admin control panel endpoints
```

## 🚀 How to Deploy on `https://deserver.neexthub.site/`

1. **Upload folder:**
   Upload the entire `dbserver/` folder to your server's web root (`public_html/dbserver/` or `/var/www/html/dbserver/`).

2. **Configure Database:**
   Set these environment variables on your server:
   ```php
   DB_HOST=localhost
   DB_PORT=3306
   DB_NAME=your_database_name
   DB_USER=your_database_user
   DB_PASS=your_database_password
   MASTER_API_KEY=your-unique-random-master-api-key
   ```
   Do not commit real credentials or reuse the example master key.

3. **Import Database Schema:**
   Import `dbserver/database.sql` into your MySQL database using phpMyAdmin, MySQL CLI, or via the Master API (`dbserver/masterapi.php?action=db_import_sql`).

4. **Verify Endpoint:**
   Visit: `https://deserver.neexthub.site/dbserver/`
   You should receive a JSON response showing `status: online`.
