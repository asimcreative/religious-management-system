# Database ERD & Relationship Diagram

Version: 1.0

## Purpose

This document defines the Entity Relationship Diagram (ERD) and relationships between all database tables.

Claude Code must use this document when creating migrations and Eloquent relationships.

---

# Company

Company

↓

hasMany

Users

Employees

Teachers

Branches

Departments

Designations

Jamaats

Quran Classes

Settings

Attendance Reasons

Notifications

Reports

Activity Logs

Audit Logs

---

# User

User

belongsTo

Company

User

hasMany

Activity Logs

Audit Logs

Notifications

---

# Employee

Employee

belongsTo

Company

Branch

Department

Designation

Current Quran Department

Current Quran Status

Employee

belongsTo (optional)

User (employees.user_id → users.id, NULLABLE)

Employee

belongsToMany

Quran Classes (via quran_class_members — with is_active, joined_at, left_at)

Jamaats (via jamaat_members — with is_active, joined_at, left_at)

Note: Employee does NOT directly belongsTo Jamaat — membership via pivot only.

Employee

hasMany

Quran Attendance

Salah Attendance

Quran Progress History

Employee

hasOne

Current Quran Progress

Teacher Profile (teachers.employee_id → employees.id, optional)

---

# Teacher

## Architecture Decision: Teacher IS an Employee

Teacher extends Employee — does NOT contain personal data fields.

Teacher

belongsTo

Company

Employee (teachers.employee_id → employees.id)

Teacher

belongsToMany

Branches (via teacher_branch)

Teacher

hasMany

Quran Classes

Quran Attendance

Quran Progress

Authentication chain: Teacher → Employee → User

---

# Quran Class

Quran Class

belongsTo

Company

Teacher

Branch

Quran Class

belongsToMany

Employees

(via quran_class_members)

Quran Class

hasMany

Quran Attendance

---

# Jamaat

Jamaat

belongsTo

Company

Branch

Leader

Vice Leader

Jamaat

belongsToMany

Employees

(via jamaat_members)

Jamaat

hasMany

Salah Attendance

---

# Quran Attendance

belongsTo

Company

Teacher

Quran Class

Employee

Attendance Reason

---

# Salah Attendance

belongsTo

Company

Prayer (FK → prayers.id, NOT a string)

Jamaat

Leader (Employee)

Employee

Attendance Reason

---

# Quran Progress

belongsTo

Employee

Teacher

Quran Department

Quran Status

---

# Quran Progress History

belongsTo

Quran Progress

Employee

Teacher

---

# Notification

belongsTo

Company

User

---

# Activity Log

belongsTo

Company

User

---

# Audit Log

belongsTo

Company

User

---

# Master Tables

Branches

Departments

Designations

Attendance Reasons

Quran Status

Quran Department

Languages

Prayers

All are referenced by Foreign Keys.

---

# Relationship Rules

Never duplicate relationships.

Always use Foreign Keys.

Always define Eloquent Relationships.

Always eager load when required.

Never delete historical data.
