# UI/UX Design System & Component Library

Version: 1.0

Project

Religious Affairs Management System (RAMS)

---

# Purpose

This document defines the complete UI Design System.

Every page, form, table and component must follow these rules.

Claude Code must NEVER create random UI.

Every screen must remain visually consistent.

---

# Design Principles

The system should feel

✓ Clean

✓ Professional

✓ Enterprise

✓ Fast

✓ Minimal

✓ Accessible

✓ Easy for Non-Technical Users

✓ Mobile Friendly

---

# Target Users

- Super Admin
- Company Admin
- HR
- Religious Affairs Department
- Quran Teacher
- Jamaat Leader
- Employees

Most users are NOT technical.

Therefore

Large buttons

Simple language

Minimum clicks

Large spacing

Easy navigation

are mandatory.

---

# Color Palette

Primary

#0F766E

Secondary

#14B8A6

Success

#22C55E

Danger

#EF4444

Warning

#F59E0B

Info

#3B82F6

Light

#F8FAFC

Dark

#1E293B

Border

#E5E7EB

Background

#F9FAFB

---

# Typography

Primary Font

Inter

Fallback

Segoe UI

Arial

Font Sizes

Page Title

28px

Card Title

20px

Section Title

18px

Body

16px

Small Text

14px

---

# Border Radius

Cards

12px

Buttons

10px

Inputs

10px

Tables

10px

Dropdowns

10px

---

# Shadows

Cards

Soft Shadow

Dropdown

Medium Shadow

Modal

Large Shadow

Avoid heavy shadows.

---

# Buttons

Primary

Save

Create

Submit

Secondary

Edit

Info

Warning

Cancel

Danger

Delete

Restore

Disabled

Gray

Loading State

Spinner Required

---

# Form Standards

Each form must include

Page Title

Breadcrumb

Save Button

Cancel Button

Validation Messages

Required Field Indicator

Help Text (when required)

---

# Input Standards

Text

Email

Password

Phone

CNIC

Date

Time

Textarea

Checkbox

Radio

Toggle

Dropdown

Multi Select

Searchable Dropdown

Implemented via Tom Select (no jQuery — see `docs/features/searchable-select/README.md`). Used on every employee/teacher picker and every employee-name filter across Reports and Report Analysis.

File Upload

Image Upload

---

# Validation UI

Green Border

Valid

Red Border

Invalid

Error Message

Below Input

Required fields

Red *

---

# Table Standards

Features

Sorting

Searching

Pagination

Export

Print

Responsive

Sticky Header

Column Visibility

Row Selection

Bulk Actions

---

# Search Standards

Global Search

Top Right

Instant Search

Debounced

Advanced Filters

Collapsible

---

# Filter Standards

Date

Branch

Department

Teacher

Leader

Attendance

Status

Clear Filters Button

---

# Dashboard Cards

Every Card

Icon

Number

Label

Trend

Mini Chart (Future)

Clickable

---

# Chart Standards

Use

Chart.js

Charts

Bar

Line

Pie

Donut

Area

No 3D Charts.

---

# Modal Standards

Small

Confirmation

Medium

CRUD

Large

Reports

Fullscreen

Future

---

# Toast Notifications

Top Right

Auto Close

Success

Green

Error

Red

Warning

Yellow

Info

Blue

---

# Loading State

Buttons

Spinner

Tables

Skeleton Loader

Dashboard

Skeleton Cards

Charts

Loading Placeholder

---

# Empty State

Every empty page should display

Illustration

Friendly Message

Create Button

---

# Confirmation Dialog

Delete

Restore

Attendance Lock

Logout

Bulk Delete

---

# Icons

Use

Bootstrap Icons

Avoid random icon libraries.

---

# Sidebar Rules

Collapsible

Icons

Permission Based

Current Menu Highlighted

---

# Navbar

Logo

Company

Search (Future)

Notifications

Language

Profile

Logout

---

# Responsive Breakpoints

Mobile

<576px

Tablet

576-992px

Desktop

992+

Large Desktop

1400+

---

# Accessibility

Keyboard Navigation

Visible Focus

High Contrast

Readable Fonts

Screen Reader Friendly

---

# RTL Support

Prepare UI for

Arabic

Urdu (Future if required)

---

# UI Performance

Lazy Load Images

Optimized SVG Icons

Minified Assets

Cached Assets

---

# Standard Page Layout

Header

↓

Toolbar

↓

Filters

↓

Table / Cards

↓

Pagination

↓

Footer

Every page must follow this layout.

---

# Future UI Features

- Dark Mode
- Theme Customizer
- Compact Mode
- High Contrast Mode
- Mobile App Design Tokens
- White Label Branding
- Custom Theme Per Company

---

# Final Rule

Claude Code must reuse existing UI components whenever possible.

Creating duplicate UI patterns is prohibited.

All new screens must follow this Design System exactly.