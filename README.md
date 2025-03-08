# Banana Tracker App (เครื่องคำนวณอายุกล้วยในตู้เย็น)

A PHP application for tracking banana shelf life in refrigerators with a messaging system between users.

## Features

- Track banana freshness and shelf life
- Calculate remaining days based on banana color and refrigeration
- Connect with other users via a fridge network
- Send messages between fridges
- Real-time status updates and notifications

## Docker Setup

### Prerequisites

- Docker
- Docker Compose

### Installation & Setup

1. Clone this repository to your local machine:

```bash
git clone https://github.com/TeyvatBytes/banana-tracker.git
cd banana-tracker
```

2. Create the necessary files:
   - Copy the provided `Dockerfile`
   - Copy the provided `docker-compose.yml`
   - Extract the SQL setup script to `setup.sql`
   - Update `database.php` to use environment variables

3. Start the application using Docker Compose:

```bash
docker-compose up -d
```

4. Access the application in your browser:

```
http://localhost:8080
```

### Environment Configuration

The Docker Compose file sets up these environment variables:

- `DB_HOST`: PostgreSQL database host
- `DB_NAME`: Database name
- `DB_USER`: Database username
- `DB_PASSWORD`: Database password

For security in production, you should change the default database password.

## File Structure

- `index.php`: Main application file
- `database.php`: Database connection configuration
- `functions.php`: Helper functions and business logic
- `setup.sql`: Database initialization script
- `Dockerfile`: Container configuration for PHP/Apache
- `docker-compose.yml`: Multi-container application setup

## Usage

1. On first login, the application creates a new fridge for you
2. Add banana lots with their initial color and refrigeration status
3. Track the shelf life of your bananas
4. Connect with other users via messaging
5. Receive notifications when bananas are about to expire

## Development

To modify the application:

1. Make changes to the PHP files
2. Changes are immediately reflected due to volume mounting in Docker Compose

## Troubleshooting

- Database connection issues: Check the environment variables in docker-compose.yml
- Permission problems: The Dockerfile sets proper permissions, but you may need to adjust them
- Port conflicts: Change the port mapping in docker-compose.yml if port 8080 is already in use

## License

This project is licensed under the MIT License.
