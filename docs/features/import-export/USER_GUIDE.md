# Importing and Exporting Data — User Guide

Every list screen in RAMS has the same toolbar. Once you have learned it on one screen you have
learned it on all of them.

```
[+ Add New]  │  [⬇ Import] [⬆ Export ▾] [📄 Download Sample]  │  [🖨] [🔄] [⋮]
```

You will only see the buttons you have permission to use.

---

## Exporting

Click **Export** and choose a format, then how much to include.

| Format | Best for |
|---|---|
| **Excel (.xlsx)** | Working with the data, or editing it and importing it back |
| **CSV** | Feeding another system |
| **PDF** | Printing, or sending to someone who only needs to read it |

| Scope | What you get |
|---|---|
| **Filtered Records** | Everything matching the filters currently applied — the usual choice |
| **Current Page** | Only the rows on the page you are looking at |
| **All Records** | Everything you have access to, ignoring filters |
| **Selected Records** | Only the rows you ticked |

The file is named after the module and the date, for example `employees_2026-08-06.xlsx`.

**Good to know**

- An export only ever contains records you are allowed to see. A Branch Manager's export covers
  their own branch, exactly as the screen does.
- Related fields are written as names, never as numbers. You will see "Main Branch", not "3".
- A PDF is limited to 5,000 rows. If your export is larger, the PDF says so on the page — use
  Excel for the complete set.
- Very large exports are prepared in the background. You will be sent to **Export History**, where
  the file appears when it is ready.

---

## Downloading a sample sheet

Click **Download Sample** before your first import. The workbook has three sheets:

1. **Template** — the columns to fill in, with one example row and drop-down lists wherever there
   is a fixed set of answers.
2. **Instructions** — what each column means, whether it is required, what it accepts and an
   example.
3. **Reference** — the values that currently exist in *your* organisation: your branches, your
   departments, and so on. Copy from here and the import cannot fail on a spelling mistake.

**Delete the example row before you upload.** If you forget, RAMS recognises it and skips it
rather than creating it as a record.

---

## Importing

1. Click **Import**.
2. Choose your file (`.xlsx`, `.xls` or `.csv`, up to 10 MB).
3. Choose what should happen to awkward rows (see below).
4. Click **Validate File**.

**Nothing is saved yet.** You are shown a summary first:

| | |
|---|---|
| **Rows in file** | How many rows were found |
| **Ready to import** | How many will be created |
| **Duplicates** | How many already exist |
| **Rows with errors** | How many cannot be imported as they stand |

Below the summary you get every problem with its **row number as it appears in your spreadsheet**,
what is wrong, and how to fix it. For example:

> Row 8 — Branch "Nowhere Branch" was not found in your company.
> *Create "Nowhere Branch" first, or use one of the values on the Reference sheet.*

If you are happy, click **Import Now**. If not, close the page — nothing has been changed.

### If some rows are invalid

- **Import the valid rows** *(default)* — good rows are saved, and the rest come back to you in an
  error file you can correct and re-upload.
- **Cancel the whole import** — nothing is saved unless every single row is valid.

### If a record already exists

- **Keep the existing record** *(default)* — your stored data is left alone and the row is
  reported as skipped.
- **Update the existing record** — the stored record is overwritten with the values in your file.
- **Treat it as an error** — the row goes into the error file.

### After the import

You get a summary of what was created, updated, skipped and failed. If anything failed there is a
**Download error report** button. That file contains your original rows plus two extra columns —
the reason and a suggested fix. Correct them in that file and upload it again; the extra columns
are ignored.

Large files are imported in the background. You can leave the page — progress is shown live, and
the result waits for you in **Import History**.

---

## History

Under the **⋮** menu on any list:

- **Import History** — every file uploaded, by whom, and what happened to each row.
- **Export History** — every export taken out, by whom, and with which filters.

You always see your own transfers. Seeing your colleagues' requires the activity-log permission.

---

## Common problems

| Message | What to do |
|---|---|
| *The file is missing required column(s)* | Your headings do not match the template. Download a fresh sample and copy your data into it. |
| *Branch "X" was not found in your company* | Create X first, or use a value from the Reference sheet. Names must match a record your organisation already has. |
| *Employee Code "X" already exists* | Use a different code, or choose "Update the existing record". |
| *Employee Code "X" appears more than once in this file* | Two rows share the same code. Remove or correct one. |
| *Status must be one of: Active, Inactive* | Use one of the listed values, or pick from the drop-down in the template. |
| *Date of Birth must be a date in the format YYYY-MM-DD* | Write dates as 1990-05-21. |
| *Attendance is locked for this date* | Attendance closes at your organisation's cut-off time. Ask an administrator with override rights. |
| *That file could not be read* | Save it as .xlsx or .csv and try again. |

---

## Tips

- **Export first, then edit, then import.** An exported file is a valid import file, so the fastest
  way to update many records is to export them, change what you need, and import with "Update the
  existing record".
- **Start small.** Import five rows before importing five thousand.
- **Use the Reference sheet.** Most failed imports are a branch or department name that does not
  quite match.
- Extra columns you add are ignored, so you can keep your own working notes in the file.
