# Library Management System

A modern, feature-rich Library Management System built with Laravel and Filament PHP, designed to manage books, members, transactions, and more in a library setting.

## Features

- **Book Management**: Add, edit, and manage book inventory
- **Member Management**: Track library members and their memberships
- **Transaction System**: Handle book checkouts, returns, and renewals
- **Stock Management**: Track and adjust book inventory levels
- **Invoice Generation**: Generate and manage invoices for transactions
- **User Roles**: Role-based access control for staff and administrators
- **Reports**: Generate various reports for library operations
- **Responsive Design**: Works on desktop and mobile devices

## Documentation

Project documentation artifacts are available in the `docs/` folder:

- `docs/00-Documentation-Index.md`
- `docs/01-Project-Proposal.md`
- `docs/02-SRS.md`
- `docs/03-Project-Plan-WBS.md`
- `docs/04-SDD.md`
- `docs/05-Test-Plan.md`
- `docs/06-Test-Summary-Report.md`
- `docs/07-Project-Closing-Report.md`
- `docs/User-Manual.md`
- `docs/Status-Reports/Status-Report-Template.md`
- `docs/Presentation-Slides-Outline.md`

## Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- Node.js & NPM (for frontend assets)
- Web server (Apache/Nginx) with URL rewriting enabled

## Installation

1. **Clone the repository**:
   ```bash
   git clone [repository-url]
   cd LibrarySystem
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Install NPM dependencies**:
   ```bash
   npm install
   npm run build
   ```

4. **Configure environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure database**:
   - Create a new MySQL database
   - Update `.env` file with your database credentials:
     ```env
     DB_DATABASE=your_database_name
     DB_USERNAME=your_database_user
     DB_PASSWORD=your_database_password
     ```

6. **Run migrations and seed the database**:
   ```bash
   php artisan migrate --seed
   ```

7. **Create storage link**:
   ```bash
   php artisan storage:link
   ```

8. **Set up the scheduler** (for overdue notifications):
   Add this to your server's crontab:
   ```bash
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```

## Usage

### Accessing the System

1. Start the development server:
   ```bash
   php artisan serve
   ```

2. Open your browser and navigate to:
   ```
   http://localhost:8000
   ```

3. Login with the default admin credentials:
   - **Email**: admin@example.com
   - **Password**: password

   *Note: Change these credentials after first login for security.*

### Key Features

#### 1. User Management
- Create and manage staff accounts with different permission levels
- Manage library members and their memberships
- Track member activity and history

#### 2. Book Management
- Add new books with details like title, author, ISBN, etc.
- Categorize books by genres
- Track book availability and location
- Manage book copies and conditions

#### 3. Stock Management
- Track book inventory levels
- Record stock adjustments (add/remove books)
- View complete stock transaction history
- Monitor stock movement with before/after quantities
- Generate stock reports

#### 4. Circulation
- Check out books to members
- Process book returns
- Handle book renewals
- Track overdue items and calculate fines
- Manage book conditions and status

#### 5. Reports
- Generate circulation reports
- View overdue items
- Track book popularity
- Generate financial reports
- Stock level and movement reports

## Configuration

The system can be configured through the admin panel or by modifying the `.env` file. Key configuration options include:

- Library information
- Fine calculation settings
- Loan periods
- Membership types and fees
- Email notifications
- Stock adjustment settings

## Security

- All passwords are hashed using bcrypt
- CSRF protection
- XSS protection
- SQL injection prevention
- Role-based access control

## Troubleshooting

1. **Permission Issues**:
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R $USER:www-data storage bootstrap/cache
   ```

2. **Cache Issues**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```

3. **Storage Link Issues**:
   ```bash
   php artisan storage:link
   ```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is open-source and available under the [MIT License](LICENSE).

## Support

For support, please open an issue in the GitHub repository or contact the development team.
