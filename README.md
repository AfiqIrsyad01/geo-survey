# GeoSurvey System (GSS) - Development Roadmap & Checklist

This document tracks the development progress and future roadmap of the GeoSurvey System (GSS), focusing on GIS operational intelligence and automated field validation.

##  Phase 1: Core GIS Infrastructure
- [x] **Project Boundary Management**: Database support for spatial project zones and boundaries.
- [x] **High-Precision Mapping**: Integration with MapLibre GL JS and OpenFreeMap for coordinate selection.
- [x] **Client-Side Spatial Validation**: Implementation of Turf.js for real-time Point-in-Polygon validation.

##  Phase 2: Multi-Role Workflow & Ecosystem
- [ ] **Role-Based Access Control (RBAC)**: Distinct interfaces and permissions for **Admin**, **HOD**, and **Field Staff**.
- [ ] **Role-Based Middleware**: Granular route protection using custom Laravel middleware.
- [ ] **HOD Approval Module**: Streamlined workflow for reviewing and approving field survey submissions.

##  Phase 3: Automated Evidence Intelligence
- [ ] **Survey Evidence Capture**: Secure multi-image upload functionality for field work.
- [ ] **GPS & Timestamp Watermarking**: Server-side image processing (Intervention Image) for embedding immutable metadata.
- [ ] **Spatial Fidelity**: Verification of image metadata against reported coordinates.

##  Phase 4: Corporate Reporting System
- [ ] **Dynamic PDF Engine**: Automated generation of professional, corporate-branded reports via DomPDF.
- [ ] **SAD Flow Logic**: Transformation of raw field data into structured operational information for stakeholders.

##  Phase 5: Future Roadmap (Planned Enhancements)
- [ ] **Real-time Workflow Notifications**: Alert system for new submissions and approvals (Migration ready).
- [ ] **Offline Field Mode**: Enabling data collection in low-connectivity areas with local storage syncing.
- [ ] **Advanced Spatial Analytics**: Implementation of regional heatmaps and trend analysis.
- [ ] **Bulk Data Operations**: Multi-project batch exports (CSV/XLSX) and bulk GIS data ingestion.

---
**Focus: Engineering GIS Intelligence. Powered by GSS.**
