# SSR Cinema - Movie Booking Website

A complete cinema booking website with PHP backend and MySQL database integration.

## Features

- **User Authentication**: Registration and login system
- **Movie Booking**: Book tickets for available movies
- **Admin Dashboard**: View booking statistics and manage the system
- **Responsive Design**: Works on desktop and mobile devices
- **Database Integration**: Full PHP/MySQL backend

## Database Structure

The system uses the following tables:

- `users` - Store user accounts and admin privileges
- `movies` - Store movie information
- `bookings` - Store ticket booking records

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or XAMPP/WAMP for local development

### Installation Steps

1. **Clone or download the project files** to your web server directory

2. **Database Setup**:

   - Create a MySQL database named `ssr_cinema`
   - Update database credentials in `config/database.php` if needed:
     ```php
     private $host = 'localhost';
     private $db_name = 'ssr_cinema';
     private $username = 'root';
     private $password = 'root';
     ```

3. **Initialize Database**:

   - Open your browser and navigate to: `http://your-domain/setup_database.php`
   - This will create all necessary tables and insert sample data
   - **Default Admin Account**:
     - Username: `admin`
     - Password: `admin123`

4. **File Permissions**:
   - Ensure PHP has read/write access to the project directory
   - Set appropriate permissions for the `php/` and `config/` directories

### Usage

#### For Users:

1. **Registration**: Click "Sign Up" to create a new account
2. **Login**: Use your credentials to log in
3. **Book Tickets**:
   - Navigate to the booking page
   - Select movie, date, and number of tickets
   - Confirm your booking

#### For Administrators:

1. **Login**: Use admin credentials (admin/admin123)
2. **Dashboard**: Access admin dashboard to view:
   - Total bookings
   - Movies currently showing
   - Total revenue
   - Tickets sold today

## File Structure

```
├── config/
│   └── database.php          # Database configuration
├── php/
│   ├── auth.php             # User authentication handler
│   └── booking.php          # Booking system handler
├── image/                   # Movie posters and images
├── index.html              # Main homepage
├── booking.html            # Ticket booking page
├── admin.html              # Admin dashboard
├── style.css               # Stylesheet
├── script.js               # JavaScript functionality
├── setup_database.php      # Database setup script
└── README.md               # This file
```

## API Endpoints

### Authentication (`php/auth.php`)

- `POST` with `action=register` - Register new user
- `POST` with `action=login` - User login
- `POST` with `action=logout` - User logout

### Booking (`php/booking.php`)

- `POST` with `action=create_booking` - Create new booking
- `POST` with `action=get_movies` - Get available movies
- `GET` with `action=get_stats` - Get booking statistics (admin)

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention using prepared statements
- Input validation and sanitization
- Session management for user authentication

## Customization

### Adding New Movies

1. Access the database directly or create an admin interface
2. Insert into the `movies` table with required fields:
   - `title`, `description`, `genre`, `image_url`

### Changing Ticket Prices

- Update the ticket price in `php/booking.php` (line with `$ticket_price = 500`)
- Update the display price in `booking.html`

### Styling

- Modify `style.css` to change the appearance
- Update colors, fonts, and layout as needed

## Troubleshooting

### Common Issues

1. **Database Connection Error**:

   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **PHP Errors**:

   - Check PHP error logs
   - Ensure all required PHP extensions are installed (PDO, MySQL)

3. **File Permissions**:
   - Ensure web server has read access to all files
   - Check directory permissions

### Support

For issues or questions:

- Check the browser console for JavaScript errors
- Review PHP error logs
- Verify database connections and queries

## License

This project is open source and available under the MIT License.
