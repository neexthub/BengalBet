# 🐅 BengalBet - Bangladesh's #1 Online Casino & Gaming Platform

> Full-stack production-grade recreation of the BengalBet casino and gaming portal featuring 21 authentic slot, crash, arcade, and table game engines with unified real-time database synchronization, bilingual Bengali/English interface, VIP progression, wallet management (bKash, Nagad, Rocket, USDT), and master administrative controls.

---

## 🌟 Key Highlights & Features

### 🎮 21 Authentic Built-In Game Engines
Every game runs realistic graphics, physics, sound synthesis via Web Audio API, paytables, cascading reels, scatter triggers, bonus games, and Provably Fair seed generation:

1. **Spribe Aviator**: Red monoplane, exponential curve, dual betting panels, auto cashout, past rounds ribbon, live social feed.
2. **JILI Super Ace (Classic)**: 5x4 grid, 1024 Ways, 4-step elimination multiplier (x1, x2, x3, x5), Golden Card to Joker flips, Big Joker lightning strikes.
3. **JILI Super Ace Deluxe**: 5x4 grid, 5-step elimination ladder (up to x10 base, x20 free spins), Star Card laser transform, Buy Bonus feature.
4. **JILI Super Ace II**: Double multiplier upgrades, Golden Ace cascade transforms, free spins re-triggers.
5. **Head & Tail (হেড অ্যান্ড টেল)**: Realistic 3D coin toss physics, 3 modes (Classic 1.96X, Consecutive Streak Ladder up to 215X with Cashout, Double Coin 3.85X).
6. **JILI Fortune Gems 3**: 3-reel slot + 4th Multiplier/Split reel, Extra Bet feature, Super Lucky Wheel bonus modal.
7. **JILI Boxing King**: 5x5 cascading reels, combo multiplier ladder (up to x8 base, x30 free spins), free spins round.
8. **FA CHAI Super Elements 2**: 6x5 grid, 3600 Ways, cascading elimination up to 10X/20X, golden element to wild transformations.
9. **PG Soft Wild Bounty Showdown**: 3-4-5-5-4-3 layout, 3600 Ways, doubling multiplier on every cascade up to 1024X, gold frames to wilds.
10. **JILI Happy Fishing**: Real arcade shooter, cannon power levels (৳1-৳100), torpedoes, laser cannon, Mega Octopus 950X Wheel.
11. **Alibaba's Cave of Fortune**: 5x4 grid, 1024 Ways, cascading tumble, Cave Urn pick bonus game.
12. **JILI Fortune Tree**: 5x3, 30 paylines, golden tree shake wilds, Hongbao envelope pick bonus.
13. **JILI Crazy 777**: 3x3 classic slot with 4th Special Reel (2X, 5X, 10X multipliers, +100 to +1000 cash prizes, and respin triggers).
14. **Seven Coins Hold & Win**: 3x3 layout, Instant Collect coins, Hold & Win 3-respin bonus with Mini/Minor/Major/Grand jackpots.
15. **Anubis Gold of Egypt**: Ancient Egyptian cascading slot with Pharaoh multipliers and free spins.
16. **Piñata Wins**: Festive Mexican cascading slot with gold frames, exploding piñatas, and progressive multipliers.
17. **JILI Money Coming**: Single-payline number combiner (forming actual cash figures like 10, 50, 1000) with Special Reel (2X, 5X, 10X, Respin, Wheel Bonus).
18. **JILI Magic Ace**: Card elimination cascades, progressive multipliers, golden card locks.
19. **Pragmatic Play Sweet Bonanza**: 6x5 tumble grid, scatter pays, free spins with tumbling bomb multipliers up to 100X.
20. **PG Soft Treasures of Aztec**: 6x5 + horizontal top reel, Wilds-on-the-Way transformations, increasing cascade multipliers.
21. **JILI Circus Joker 4096**: 6x4 layout, 4096 Ways to Win, animated Multiplier Wheel up to 4096X.

---

### 🗄️ Unified Database Server & Real-Time Sync
- **Central Game Engine (`src/services/gameEngine.ts`)**: Every single bet, win, and zero-win spin across all 21 games immediately records via `gameEngine.recordRound(...)` to the REST database backend (`/api/games/bet`).
- **Telemetry & Dynamic Balancing**: Real-time RTP monitoring, house edge enforcement, win/loss history, and profit/loss calculation.
- **REST Database Backend (`/dbserver`)**: Complete PHP/MySQL REST API located in the repo with automatic schema migrations, self-healing database tables, and connection monitoring.
- **Client Fallback & Resilience**: Transparent retry mechanisms and offline safety guarantees so gameplay is never disrupted.

---

### 🛡️ Master Administration Suite
Access via `/admin` or the header controls:
- **User Management**: Search, view user profiles, adjust balances (Add / Deduct / Set), modify VIP level, reset claim cooldowns, ban/freeze accounts.
- **Financial Ledger**: Real-time deposit/withdrawal requests with instant approve/reject actions and proof verification.
- **Live Chat & Support Tickets**: 24/7 support console with real-time agent responses and canned quick replies.
- **Affiliate & Referral Control**: Multi-tier commission management and instant referral rewards.
- **Game Engine Manager**: Adjust global RTP (0% - 100%), per-game RTP, volatility, near-miss rates, and house caps.

---

## 🛠️ Tech Stack

- **Frontend**: React 19, TypeScript, Vite, Tailwind CSS v4, Lucide Icons, Canvas Confetti.
- **Backend**: Node.js, Express, tsx / esbuild.
- **Database Server**: PHP 8+, MySQL / MariaDB (or SQLite), PDO self-healing schema (`/dbserver`).
- **Authentication**: Native phone/username auth + Google OAuth 2.0.

---

## 🚀 Getting Started

### 1. Prerequisites
- **Node.js**: v18.0.0 or higher
- **npm** or **bun**
- (Optional) **PHP 8.0+** & **MySQL** (for self-hosting the `/dbserver` backend)

### 2. Installation
```bash
# Clone the repository
git clone https://github.com/your-username/bengalbet-casino-portal.git

# Enter the project directory
cd bengalbet-casino-portal

# Install dependencies
npm install
```

### 3. Environment Variables
Copy the example environment file:
```bash
cp .env.example .env
```
Fill in your configuration:
```env
PORT=3000
NODE_ENV=development

# Google OAuth 2.0 (Optional)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret

# Client Configuration
VITE_APP_TITLE="BengalBet Casino & Gaming Portal"
VITE_APP_DEFAULT_LANG="bn"
```

### 4. Running the Development Server
```bash
npm run dev
```
Open [http://localhost:3000](http://localhost:3000) in your browser.

---

## 📦 Production Deployment

### Building for Production
```bash
# Compile frontend and bundle Express server
npm run build

# Start production server
npm start
```

### Deploying the REST Database Server (`dbserver/`)
1. Upload the contents of the `dbserver/` folder to your web hosting public directory (e.g. `public_html/dbserver/`).
2. Update MySQL database credentials in `dbserver/config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   define('MASTER_API_KEY', 'your-unique-random-master-api-key');
   ```
3. Import the initial database schema from `dbserver/database.sql` via phpMyAdmin or MySQL CLI.
4. Test the health endpoint: `https://your-domain.com/dbserver/` (should return JSON `{ "status": "online" }`).

---

## 📁 Repository Directory Structure

```text
├── dbserver/                   # Full PHP/MySQL REST API server & database dump
│   ├── api/                    # Modular API endpoints (auth, wallet, games, admin, etc.)
│   ├── config.php              # Server config, CORS, MySQL credentials
│   ├── database.sql            # Complete database schema
│   ├── db.php                  # PDO database handler with auto-repair
│   ├── index.php               # REST API gateway
│   └── masterapi.php           # Master management endpoint
├── src/
│   ├── assets/                 # Icons, badges, banners, high-res game thumbnails
│   ├── components/             # React UI components (Modals, Wallets, Admin views, Lobby)
│   ├── data/                   # Game registries, provider catalogues, promotion configs
│   ├── games/                  # 21 Authentic standalone game engines
│   │   ├── alibaba/            # Alibaba's Cave of Fortune
│   │   ├── anubis/             # Anubis Egyptian Slot
│   │   ├── aviator/            # Spribe Aviator Crash Game
│   │   ├── aztec/              # Treasures of Aztec
│   │   ├── boxingking/         # Boxing King Cascading Slot
│   │   ├── circus/             # Circus Joker 4096
│   │   ├── crazy777/           # Crazy 777 Special Reel Slot
│   │   ├── fortunegems/        # Fortune Gems 3 & Lucky Wheel
│   │   ├── fortunetree/        # Fortune Tree & Hongbao Bonus
│   │   ├── happyfishing/       # Happy Fishing Arcade Shooter
│   │   ├── headtail/           # 3D Coin Toss Game (Head & Tail)
│   │   ├── magicace/           # Magic Ace Cascading Slot
│   │   ├── moneycoming/        # Money Coming & Super Lucky Wheel
│   │   ├── olympus/            # Gates of Olympus Multiplier Slot
│   │   ├── pinata/             # Piñata Wins Cascading Slot
│   │   ├── sevencoins/         # Seven Coins Hold & Win
│   │   ├── superace/           # Super Ace (Classic, Deluxe, II)
│   │   ├── superelements/      # Super Elements 2 Cascading Slot
│   │   ├── sweetbonanza/       # Sweet Bonanza Tumbling Cluster Slot
│   │   └── wildbounty/         # Wild Bounty Showdown 1024X Slot
│   ├── services/
│   │   ├── api.ts              # Universal client API service
│   │   ├── gameEngine.ts       # Unified telemetry, RTP calculation & round recording
│   │   └── firebase.ts         # Optional Firebase / Firestore SDK connector
│   ├── types/                  # TypeScript interfaces and models
│   ├── App.tsx                 # Main application controller
│   └── main.tsx                # React root entry point
├── server.ts                   # Express server with Vite middleware integration
├── package.json                # Project dependencies and build scripts
└── tsconfig.json               # TypeScript compiler configuration
```

---

## 🔒 Security & Best Practices

- **Zero Client-Side Secrets**: All sensitive keys and server tokens are kept server-side or in private environment files.
- **SQL Injection Prevention**: All SQL queries in `dbserver` utilize parameterized PDO statements.
- **RTP Integrity**: Game outcomes are calculated mathematically using configured RTP parameters with dynamic drift balancing.
- **CORS Protection**: Restricted origins and verified API authorization tokens.

---

## 📄 License
This project is licensed under the MIT License. Developed for Neext Hub.
