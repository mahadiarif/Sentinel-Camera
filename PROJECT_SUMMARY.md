# Sentinel AI: SuperShop Intelligence & Surveillance System

Sentinel is a high-performance, real-time AI computer vision system designed for security and business intelligence. It bridges the gap between traditional CCTV and actionable data insights.

---

## 🛠️ System Architecture

Sentinel uses a professional **Distributed Architecture**:

1.  **AI Edge Worker (Python/YOLO12)**:
    *   Directly connects to cameras (USB, RTSP, Hikvision/Dahua).
    *   Uses **YOLO12 + ByteTrack** for object detection and unique individual tracking.
    *   Performs **IoU Deduplication** to ensure each person/object is only counted once.
2.  **Central Dashboard (Laravel/PHP)**:
    *   Provides a premium, glassmorphism UI for monitoring.
    *   Manages camera configurations and records historical detection data.
    *   Features real-time polling to keep the dashboard "live" without page refreshes.

---

## 🚀 Key Features for SuperShops

We have optimized the system specifically for retail environments:

*   **Unique Customer Counting**: Counts actual visitors entering the shop, ignoring staff repetitions.
*   **Real-time Queue Monitor**: Automatically detects if billing queues are becoming "Normal", "Busy", or "Heavy".
*   **Checkout Wait Estimation**: Predicts actual customer wait time based on the density of the crowd at counters.
*   **Smart Activity Feed**: Logs every interaction (e.g., Person detected with Bag/Laptop) with localized timestamps.
*   **Snapshot Archiving**: Automatically captures high-resolution images of every unique detection.

---

## 📊 Live Monitoring Stats

*   **Live Feed Terminal**: 1 FPS polling for low-latency visual monitoring.
*   **Breakdown by Class**: Real-time chart showing the most detected object categories in-store.
*   **Snapshot Grid**: A quick scrollable history of the latest people/events captured by the AI.

---

## 🏁 Recent Improvements (Completed)

*   [x] **Fixed Database Sync**: Moved from obsolete `GateDetection` to unified `Detection` system.
*   [x] **Fixed Image Loading**: Corrected `snapshots` vs `gate_snapshots` path conflicts.
*   [x] **Removed Duplicates**: Added IoU logic so one person doesn't show up twice.
*   [x] **Retail Redesign**: Swapped generic security counts for SuperShop Business Metrics.

---

> [!TIP]
> To keep the system running, ensure both the Laravel server (`php artisan serve`) and the AI Worker (`python worker.py`) are active in the background.
