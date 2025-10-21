# Payment Requisition System

A comprehensive PHP MySQL web application for managing payment requisitions with multi-level approval workflow.

## Features

- **User Authentication & Authorization**
  - Secure login/logout system
  - Role-based access control (Admin, Approver, User)
  - User level management (1-5)

- **Requisition Management**
  - Create, edit, and track requisitions
  - Multi-level approval workflow
  - File attachments support
  - Priority levels (Low, Medium, High, Urgent)
  - Status tracking (Draft, Pending, Approved, Rejected)

- **Approval System**
  - Configurable approval levels
  - Department-specific approvals
  - Comments and feedback system
  - Email notifications (planned)

- **Dashboard & Reports**
  - Real-time statistics
  - Department-wise analytics
  - Export functionality (PDF, Excel)
  - Activity tracking

- **User Management**
  - Staff records management
  - User roles and permissions
  - Department assignments

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript
- **Styling**: Tailwind CSS
- **Icons**: Font Awesome
- **Server**: Apache (XAMPP)

## Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser
- Text editor (optional)

### Setup Instructions

1. **Download and Install XAMPP**
   - Download from [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Install and start Apache and MySQL services

2. **Clone/Download the Project**
   ```bash
   # Place the project files in XAMPP's htdocs directory
   # Example: C:\xampp\htdocs\requisition\
   ```

3. **Database Setup**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Import the database schema from `database/schema.sql`
   - Or run the SQL commands manually

4. **Configuration**
   - Update database credentials in `config/database.php` if needed
   - Default settings work with standard XAMPP installation

5. **Access the Application**
   - Open browser and go to `http://localhost/requisition/`
   - Default admin login:
     - Email: admin@requisition.com
     - Password: admin123

## Project Structure

```
requisition/
├── config/
│   ├── config.php          # Main configuration
│   └── database.php        # Database connection
├── classes/
│   └── Auth.php           # Authentication class
├── models/
│   ├── Requisition.php    # Requisition model
│   └── User.php           # User model
├── includes/
│   ├── header.php         # Header component
│   └── sidebar.php        # Sidebar navigation
├── assets/
│   ├── css/
│   │   └── style.css      # Custom styles
│   └── js/
│       └── app.js         # JavaScript functionality
├── database/
│   └── schema.sql         # Database schema
├── uploads/               # File uploads directory
├── index.php             # Dashboard
├── login.php             # Login page
├── create-requisition.php # Create requisition
├── requisitions.php      # List requisitions
├── approvals.php         # Approval queue
├── users.php             # User management
├── reports.php           # Reports
└── README.md             # This file
```

## Database Schema

### Main Tables
- `users` - System users and authentication
- `staffs` - Staff information and details
- `requisitions` - Payment requisitions
- `approval_levels` - Approval workflow configuration
- `approval_steps` - Individual approval steps
- `requisition_attachments` - File attachments
- `notifications` - System notifications

## Usage

### Creating a Requisition
1. Login to the system
2. Click "Create New Requisition"
3. Fill in all required fields
4. Add attachments if needed
5. Submit or save as draft

### Approval Process
1. Approvers receive notifications
2. Review requisition details
3. Add comments and approve/reject
4. System automatically moves to next level

### User Management
1. Admin users can manage system users
2. Assign roles and permissions
3. Set approval levels
4. Manage staff records

## Security Features

- Password hashing using PHP's password_hash()
- SQL injection prevention with prepared statements
- XSS protection with input sanitization
- Session management
- Role-based access control
- CSRF protection (planned)

## Customization

### Adding New Approval Levels
1. Update `approval_levels` table
2. Modify approval workflow logic
3. Update UI components

### Custom Fields
1. Add columns to relevant tables
2. Update model classes
3. Modify forms and views

### Styling
- Modify `assets/css/style.css` for custom styles
- Tailwind CSS classes can be customized
- Update color scheme in configuration

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check MySQL service is running
   - Verify database credentials in config
   - Ensure database exists

2. **Permission Errors**
   - Check file permissions on uploads directory
   - Ensure Apache has write access

3. **Login Issues**
   - Verify default admin user exists
   - Check password hash in database
   - Clear browser cache/cookies

### Debug Mode
- Enable error reporting in `config/config.php`
- Check Apache error logs
- Use browser developer tools

## Contributing

1. Fork the repository
2. Create feature branch
3. Make changes
4. Test thoroughly
5. Submit pull request

## License

This project is open source and available under the MIT License.

## Support

For support and questions:
- Check the documentation
- Review common issues
- Contact system administrator

## Version History

- v1.0.0 - Initial release
  - Basic requisition management
  - Multi-level approval system
  - User authentication
  - Dashboard and reports

## Future Enhancements

- Email notifications
- Mobile responsive design
- API endpoints
- Advanced reporting
- Document management
- Audit trail
- Integration capabilities