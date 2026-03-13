# GeoSurvey System (GSS)
### Corporate GIS Operational Intelligence Platform

GeoSurvey System (GSS) is a high-performance, industry-ready GIS platform designed to streamline field data collection, spatial validation, and corporate reporting. Built on the **SAD Flow** (System Analysis & Design) principle, GSS converts raw field inputs into actionable operational intelligence.

---

## 🚀 Key Features

### 🗺️ GIS Framework & Spatial Validation
- **High-Precision Mapping**: Integrated with **MapLibre GL JS** using robust **OpenFreeMap** styles for real-time coordinate picking.
- **Smart Boundary Validation**: Leverages **Turf.js** for client-side **Point-in-Polygon** checks, ensuring field staff are within their assigned project zones before data submission.
- **Project Zone Control**: Administrative ability to define geometric project boundaries in the database (MySQL Spatial Data).

### 📸 Automated Evidence Intelligence
- **GPS-Timestamp Watermarking**: Automatic burning of GPS coordinates (Lat/Long) and server-side timestamps onto survey images using the **Intervention Image** library.
- **Immutable Log**: Ensures field evidence is authentic and verified against the reported spatial coordinates.

### 📄 Professional Reporting System
- **PDF Report Generation**: Converts survey details into professional, corporate-branded PDF reports via **DomPDF**.
- **SAD Flow Output**: Generates structured summaries (Information) from raw site details (Data) for HOD and Stakeholder review.

### 🔐 Secure Multi-Role Ecosystem
- **Role-Based Access Control (RBAC)**: Dedicated workflows for **Admin**, **HOD**, and **Staff**.
- **Middleware Protection**: Custom `role` middleware ensuring granular security across the dashboard, projects, and survey management.

---

## 🛠️ Technology Stack

| Layer | Technology |
| :--- | :--- |
| **Backend** | Laravel 10 (PHP 8.2+) |
| **Frontend** | Vue.js 3, Inertia.js |
| **Styling** | Tailwind CSS (Custom Geo-Navy Theme) |
| **GIS / Mapping** | MapLibre GL JS, Turf.js |
| **PDF Engine** | DomPDF |
| **Image Processing** | Intervention Image (GD) |
| **Database** | MySQL 8.0+ (Spatial Extensions) |

---

## 📦 Installation & Setup

1. **Clone the repository**
2. **Environment Configuration**:
   ```bash
   composer install
   npm install
   cp .env.example .env
   php artisan key:generate
   ```
3. **Database Preparation**:
   - Create a database named `geoservedb` in your MySQL environment.
   - Configure `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `.env`.
4. **Migrations & Seeding**:
   ```bash
   php artisan migrate:fresh --seed
   php artisan storage:link
   ```
5. **Run Development Servers**:
   ```bash
   php artisan serve
   # In a separate terminal
   npm run dev
   ```

---

## 🔑 Authorized Access (Seeded)

| Role | Email | Password |
| :--- | :--- | :--- |
| **Global Admin** | `admin@geosurvey.com` | `password` |
| **HOD (Reviewer)** | `hod@geosurvey.com` | `password` |
| **Field Staff** | `staff@geosurvey.com` | `password` |

---

## 🏛️ Design System (Geo-Theme)
GSS uses a custom corporate aesthetic defined in `tailwind.config.js`:
- **Geo-Navy** (`#0a192f`): Primary professional background.
- **Geo-Teal** (`#64ffda`): Highlight and GIS validation indicators.
- **Geo-Slate** (`#8892b0`): Detailed meta-data and descriptions.

---
**Build on Excellence. Powered by GSS Intelligence.**
