# UI Screen Wireframes & Page Specification

Version: 1.0

Project:
Religious Affairs Management System (RAMS)

---

# Overview

This document defines every screen of the system.

Claude Code must NOT design UI on its own.

Every page must follow this document.

The UI must be

✓ Simple

✓ Fast

✓ Urdu Friendly

✓ English Friendly

✓ Mobile Responsive

✓ Large Buttons

✓ Minimum Clicks

✓ Easy for Non-Technical Users

---

# Global Layout

------------------------------------------

Top Navbar

------------------------------------------

Logo

Company Name

Language Switch (EN / اردو)

Notifications

Profile

Logout

------------------------------------------

Sidebar

Dashboard

Employees

Teachers

Quran

Salah

Reports

Masters

Users

Roles

Settings

------------------------------------------

Main Content

------------------------------------------

Page Title

Breadcrumb

Action Buttons

Search

Filters

Table

Pagination

------------------------------------------

Footer

Version

Company

Copyright

---

# Login Screen

Fields

Email

Password

Remember Me

Language Selector

Login Button

Forgot Password

Validation

Responsive

---

# Dashboard

Cards

Total Employees

Total Teachers

Today's Quran Attendance

Today's Salah Attendance

Active Classes

Active Jamaats

Pending Attendance

Completed Attendance

Charts

Employee Per Branch

Department Wise Employees

Attendance Trend

Prayer Trend

Quran Progress

Teacher Performance

Latest Activities

Notifications

Quick Actions

---

# Employee List

Header

Create Employee

Import

Export

Print

Filters

Search

Department

Designation

Branch

Status

Table

Employee ID

Name

Department

Designation

Branch

Mobile

Status

Actions

View

Edit

Delete

---

# Employee Form

Personal Information

Employee ID

Name

CNIC

Mobile

Email

Date of Birth

Gender

Department

Designation

Branch

Photo

Current Quran Status

Current Quran Department

Save

Cancel

---

# Teacher List

Search

Filters

Branch

Status

Teacher Name

Class Strength

Actions

---

# Teacher Form

Teacher ID

Teacher Name

CNIC

Mobile

Email

Select Multiple Branches

Assign Class Members

Auto Strength

Photo

Save

---

# Quran Classes

Cards

Total Classes

Today's Attendance

Absent

Present

Table

Teacher

Branch

Strength

Status

Actions

---

# Quran Attendance

Teacher

↓

Select Class

↓

Date

↓

Student List

For each student

Photo

Employee ID

Employee Name

Attendance Dropdown

Remarks

Save

Submit

Attendance colors

Green

Present

Red

Absent

Blue

Leave

Yellow

Other

---

# Quran Progress

Employee

↓

Current Department

Current Status

Current Lesson

Current Page

Current Sipara

Current Surah

Completion %

Remarks

Update

History Button

---

# Jamaat List

Cards

Total Jamaats

Today's Attendance

Prayer Completion

Table

Leader

Vice Leader

Strength

Branch

Actions

---

# Jamaat Form

Jamaat Number

Branch

Leader

Vice Leader

Members

Auto Strength

Save

---

# Salah Attendance

Leader

↓

Prayer Selection

↓

Date

↓

Members

Attendance

Remarks

Submit

Five prayer tabs

Fajr

Dhuhr

Asr

Maghrib

Isha

---

# Reports

Filters

Date Range

Branch

Department

Teacher

Leader

Prayer

Attendance

Employee

Buttons

Search

Export Excel

Export PDF

CSV

Print

---

# Masters

Branch

Department

Designation

Attendance Reasons

Languages

Quran Status

Quran Department

Each page

CRUD

Search

Filter

Export

---

# Users

User List

Role

Permissions

Status

Actions

---

# Roles

Role Name

Permission Groups

Module Permissions

Save

---

# Profile

Photo

Language

Password

Preferences

Save

---

# Settings

Company Information

Logo

Language

Timezone

SMTP

Security

Attendance Settings

Dashboard Settings

Save

---

# Notifications

Unread

Read

Mark Read

Delete

---

# Activity Logs

Filters

User

Date

Module

Action

Search

Export

---

# Audit Logs

Filters

User

Module

Record

Date

Old Value

New Value

View Details

---

# Mobile Layout

Bottom Navigation

Dashboard

Attendance

Reports

Profile

Menu

Large Buttons

One Hand Usage

No horizontal scrolling

---

# UI Rules

All buttons must have icons.

All forms must support keyboard navigation.

All tables must support sorting.

All tables must support filtering.

Every page must support pagination.

Every destructive action must show confirmation.

Every success action must show toast notification.

Every error must show user-friendly message.

No popup should exceed screen size.

Dark Mode support (Future).

RTL support (Future Arabic).