# EVSU Web-Based Face Recognition Classroom Attendance System

This project is a web-based classroom attendance system built using the CakePHP framework.
It uses browser-based face recognition technology to automatically record student attendance in real time.
Follow the steps below to set up the project and run the system locally.

_ _ _

## Requirements 
Requirements
Make sure the following are installed on your system:
- PHP 8.0 or newer
- Composer
- Supabase
- Github Desktop
- Brevo
- Railway

## Check your PHP version:

php -v

Check Composer:

composer -v

## Clone the Repository
git clone https://github.com/yourusername/your-repository-name.git
cd your-repository-name

## Create and Activate a Virtual Environment
Option 1: Using php-virtualenv (Recommended for PHP)
Install php-virtualenv:
pip install php-virtualenv

_ _ _ 

## Create a virtual environment:

php-venv create myenv


## Activate the environment:

Linux/macOS:

source myenv/bin/activate


Windows (Command Prompt):

myenv\Scripts\activate

Option 2: Composer-Based Isolation
Install project dependencies in an isolated directory:
composer install --prefer-dist --no-dev -o
(This ensures dependencies are contained within the project folder.)

## Install Dependencies

composer install


## Run the Development Server
Start CakePHP’s built-in server:

bin/cake server

You should see:
Built-in server is running in http://localhost:8765/
Open your browser and go to:
http://localhost:8080

## Access the Admin Panel
If the admin panel is enabled, visit:
http://localhost:8080/Evsuadmin
Login using the administrator credentials created during setup.





Instructors and admins can view and export attendance records.
