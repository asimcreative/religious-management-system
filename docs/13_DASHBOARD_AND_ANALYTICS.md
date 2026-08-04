# Dashboard & Analytics

## Overview

The Dashboard is the most important screen of the system.

Every logged-in user should see a dashboard according to their assigned Role and Permissions.

No user should see information outside their authorization.

Dashboard data must always be real-time.

No values should be hardcoded.

---

# Dashboard Types

The system will have separate dashboards for

- Super Admin
- Company Admin
- Religious Affairs Admin
- HR
- Quran Teacher
- Jamaat Leader
- Employee (Future)

---

# Super Admin Dashboard

Widgets

- Total Companies
- Active Companies
- Suspended Companies
- Total Users
- Total Employees
- Total Teachers
- Total Quran Classes
- Total Jamaats
- Total Attendance Today
- System Health
- Active Sessions
- Storage Usage
- Queue Status
- Failed Jobs
- Latest Activity
- Audit Logs

Charts

- Company Growth
- Monthly Active Users
- Daily Attendance
- Companies by Status
- Companies by Country (Future)

---

# Company Admin Dashboard

Widgets

- Total Employees
- Active Employees
- Total Teachers
- Active Teachers
- Total Quran Classes
- Total Jamaats
- Today's Quran Attendance
- Today's Salah Attendance
- Average Attendance
- Total Branches
- Total Departments

Charts

- Monthly Attendance
- Quran Progress
- Branch Comparison
- Department Comparison
- Teacher Comparison
- Jamaat Comparison

---

# Quran Teacher Dashboard

Widgets

- Today's Classes
- Total Students
- Pending Attendance
- Completed Attendance
- Current Attendance %
- Average Quran Progress

Charts

- Monthly Attendance
- Student Progress
- Class Performance

---

# Jamaat Leader Dashboard

Widgets

- My Jamaat
- Total Members
- Today's Prayer Attendance
- Pending Prayer Attendance
- Attendance %

Charts

- Prayer Wise Attendance
- Monthly Attendance
- Member Performance

---

# HR Dashboard

Widgets

- Total Employees
- New Employees
- Employee Status
- Department Statistics
- Branch Statistics

Charts

- Department Strength
- Designation Distribution
- Employee Growth

---

# Dashboard Filters

Every dashboard should support

- Date Range
- Branch
- Department
- Teacher
- Jamaat
- Employee
- Attendance Status

---

# KPI Cards

Examples

Employees

Teachers

Classes

Jamaats

Present Today

Absent Today

Office Leave

Attendance %

Progress %

Completion %

---

# Quran Analytics

Total Students

Qaida Students

Nazra Students

Hifz Students

Completed Students

Teacher Performance

Class Performance

Branch Performance

Average Progress %

---

# Salah Analytics

Prayer Wise Attendance

Best Jamaat

Lowest Attendance Jamaat

Leader Performance

Branch Performance

Department Performance

Attendance Trend

---

# Attendance Analytics

Daily

Weekly

Monthly

Quarterly

Yearly

Custom Date Range

---

# Graph Types

Line Chart

Bar Chart

Pie Chart

Area Chart

Donut Chart

Progress Cards

Heat Map (Future)

---

# Live Dashboard

Dashboard widgets should refresh automatically.

Refresh interval should be configurable.

Default

5 Minutes

---

# Export Dashboard

Dashboard should support

- PDF
- Print
- Excel (Statistics)

---

# Business Rules

Rule 1

Dashboard data must respect Company Isolation.

---

Rule 2

Dashboard data must respect Role Permissions.

---

Rule 3

Dashboard widgets should only display authorized information.

---

Rule 4

Heavy dashboard calculations should use Cache.

---

Rule 5

Dashboard statistics should never execute expensive queries repeatedly.

Use caching and scheduled calculations where appropriate.

---

# Future Features

AI Insights

Attendance Prediction

Low Attendance Alerts

Teacher Performance Score

Employee Religious Engagement Score

Custom Dashboard Builder

Drag & Drop Widgets

Email Dashboard Summary

WhatsApp Summary

Scheduled Reports