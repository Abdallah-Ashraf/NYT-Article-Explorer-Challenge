# NYT-Article-Explorer-Challenge

A secure RESTful API that allows users to search New York Times articles, manage a favorites list, authenticate via JWT, rate limiting, and logging.

## Features
- **Search Articles** using the New York Times API with pagination.
- **View Article Details** from the NYT API.
- **Favorites Management** (Add/Remove/View favorite articles).
- **JWT Authentication Only For Favorites Management Apis** for secure access.
- **Rate Limiting** to prevent abuse (5 requests per 5 minutes per IP/token).
- **Logging** for requests and responses.
- **CORS Support** for frontend interaction.
- **Docker Support** for easy setup.


## Prerequisites
Ensure you have the following installed:
- **Docker & Docker Compose**
- [Git](https://git-scm.com/) installed on your system.
- A terminal or command-line interface.
- (Optional) A Git client like GitHub Desktop if you prefer a GUI.

## Getting Started with Git

To fetch and set up this project from Git, follow these instructions:

### Steps to Fetch the Project

1. **Clone the Repository**
   Open your terminal and run the following command to clone the repository to your local machine:
   ```bash
   git clone https://github.com/Abdallah-Ashraf/NYT-Article-Explorer-Challenge.git

---
## Installation
### 1. Build the Docker container
```bash 
   docker-compose build
```

### 2. Start the container
```bash
   docker-compose up -d
```

### 3. Enter the container within bash terminal
```sh
   docker exec -it nyt-article-explorer bash
```

### 4. Run Database Setup
```sh
   sqlite3 storage/database.sqlite < migration.sql
```

### 5. Add Dependencies
```bash
   composer require firebase/php-jwt
   composer require --dev phpunit/phpunit

```


###  Access the API
The API will be available at:
```
http://localhost:8085
```

---
## Running Tests
Run PHPUnit tests:
Unit tests were applied for Get Favorites and Delete Article From Favorites.
```sh
  vendor/bin/phpunit tests/FavoriteServiceTest.php
```

---
## API Endpoints Postman API documentation
```http
https://documenter.getpostman.com/view/20451058/2sAYdbQZAv
```
---
## Rate Limiting
Each user (IP or token) is allowed **5 requests per 5 minutes**. Exceeding the limit returns:
```json
{
  "error": "Too many requests. Please try again later.",
  "retry_after": 300
}
```

### Exit container bash terminal when done
```bash
   exit  
```

### Stop Containers 
```bash
   docker-compose down
```


---
## Troubleshooting

### Docker Issues
Check logs with:
```sh
    docker-compose logs -f
```

