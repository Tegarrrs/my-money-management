# Dompetra — Personal Finance Management

Dompetra is a Laravel-based web application for tracking personal income, expenses, wallets, and budgets. It is built as a portfolio project with an emphasis on practical financial workflows, modular application code, and a clean user experience.

## Highlights

- Record income, expenses, wallet transfers, and split transactions.
- Manage wallets, categories, recurring transactions, and monthly budgets.
- Import transactions from CSV or Excel files.
- Scan receipt images into transaction drafts with optional Gemini-powered OCR.
- Explore reports with cash-flow trends, category breakdowns, period comparisons, unusual-transaction detection, PDF export, and Excel export.
- Use optional Gemini analysis only when explicitly requested from the report screen; the application keeps a local analysis fallback.
- Register with email/password or Google OAuth when configured.

## Tech stack

- PHP 8.3+, Laravel 13, and SQLite by default (MySQL is also supported through Laravel configuration)
- Blade, Vite, Tailwind CSS, and Alpine.js
- Pest for automated tests
- PhpSpreadsheet and Dompdf for report exports

## Run locally

### Requirements

- PHP 8.3+ with SQLite support
- Composer
- Node.js 22+ and npm

### Installation

```bash
git clone https://github.com/Tegarrrs/my-money-management.git
cd my-money-management
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Create the default SQLite database, run migrations and seed starter categories:

```bash
# Windows PowerShell
New-Item -Path database -Name database.sqlite -ItemType File

php artisan migrate --seed
npm run build
php artisan serve
```

The app is then available at `http://127.0.0.1:8000`.

> On Linux, macOS, or Git Bash, use `touch database/database.sqlite` instead.

## Optional integrations

All integrations are opt-in. Keep real credentials only in your local `.env` file, which is excluded from Git.

### Gemini OCR and report analysis

Add your Google AI Studio key to `.env`:

```env
GEMINI_API_KEY=your_key_here
GEMINI_REPORT_MODEL=gemini-flash-latest
```

Receipt OCR sends the selected receipt image to Gemini. Report analysis sends a summary of the selected reporting period only after the user presses the analysis action.

### Google OAuth

Create OAuth credentials in Google Cloud Console and configure:

```env
GOOGLE_CLIENT_ID=your_client_id
GOOGLE_CLIENT_SECRET=your_client_secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

## Quality checks

```bash
php artisan test
npm run build
```

GitHub Actions runs these checks for pull requests and pushes to `main`.

## Project structure

```text
app/
├── Actions/          Application use cases
├── DTO/              Typed data transfer objects
├── Http/             Controllers and request validation
├── Infrastructure/   OCR and external-service implementations
├── Models/           Eloquent persistence models
└── Services/         Reporting, budgeting, dashboard, and parsing logic
```

## Security and privacy

Do not commit `.env` files, API keys, database dumps, uploaded receipts, or personal financial data. See [SECURITY.md](SECURITY.md) for reporting guidance.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## License

This project is released under the [MIT License](LICENSE).
