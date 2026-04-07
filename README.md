# MetroNet — Object Detection & Training System

A real-time AI-powered surveillance system built with Laravel 12 and YOLOv8. Monitor camera feeds, receive instant alerts for detected objects, and train custom AI models via a web interface.

## System Features
- **24/7 Gate Monitoring**: RTSP or USB camera integration.
- **AI Detection**: Powered by Ultralytics YOLOv8 for high-accuracy object detection.
- **Real-time Dashboard**: Live feed updates, object counts, and instant alert notifications.
- **Custom Object Training**: Upload images, label them on the web, and train new models with one click.
- **Activity Logs**: Searchable history of all detections with snapshots.

---

## 🚀 Setup Guide

### 1. Requirements
- PHP 8.2+
- MySQL 8.0+
- Python 3.10+
- Composer & Node.js

### 2. Laravel Installation
```bash
# Clone the repository and enter the folder
cd sentinel

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure your database in .env, then run migrations
php artisan migrate

# Create initial storage folders
mkdir -p public/gate_frames public/gate_snapshots
mkdir -p public/training_data public/custom_models
chmod -R 775 public/gate_frames public/gate_snapshots
chmod -R 775 public/training_data public/custom_models

# Start the server
php artisan serve
```

### 3. Queue Worker (Crucial for Training)
In a separate terminal, start the queue worker to process training jobs:
```bash
php artisan queue:work --queue=training
```

### 4. Python Worker Setup
The AI engine runs as a separate worker script.
```bash
cd python_worker
python3 -m venv venv
# Windows: venv\Scripts\activate | Linux: source venv/bin/activate
pip install -r requirements.txt
cp .env.example .env

# Configure camera source and Laravel API details in .env
python gate_worker.py
```

### 5. Real-time Broadcasting
MetroNet uses Laravel Echo. Choose your preferred driver in `.env`:
- **Option A (Pusher)**: Add your Pusher.com keys.
- **Option B (Soketi)**: Self-host with `npm install -g @soketi/soketi` and start with `soketi start`.

---

## 🔐 Default Access
- **URL**: `http://localhost:8000`
- **Default Admin**: `admin@sentinel.com` / `password`
(Note: Create this user via `php artisan tinker`: `User::create(['name' => 'Admin', 'email' => 'admin@sentinel.com', 'password' => Hash::make('password')])`)
