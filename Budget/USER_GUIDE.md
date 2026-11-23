# Budget Module - Quick User Guide

## 🚀 Getting Started

### Accessing Budget Management
Navigate to: `Budget > Manage` in your application menu

## 📊 Main Features

### 1. Budget List (Manage.php)
**What you'll see:**
- **Statistics Dashboard** - Quick overview of your budgets
  - Total number of budgets
  - Active budgets count
  - Number of projects with budgets
- **Action Buttons** - Quick access to reports
- **Budget Table** - Complete list of all budgets with:
  - Budget Title
  - Sub Title
  - Project Name
  - Creation Date
  - Status (Active/Inactive)
  - Edit & Delete actions

**Actions:**
- ➕ **Add New Budget** - Click the "+" button
- ✏️ **Edit Budget** - Click the edit icon on any row
- 🗑️ **Delete Budget** - Click delete (with confirmation prompt)
- 📊 **View Report** - Click "View BVE Report" button

### 2. Creating/Editing Budget (Insertupdate.php)

#### Step 1: Basic Information
Fill in the following fields:
- **Budget Title** - Main title for the budget (required)
- **Sub Title** - Additional description (required)
- **Project** - Select from dropdown (required)

#### Step 2: Budget Line Items
For each line item (Head of Account), provide:
1. **Head of Account** - Select the expense category
2. **Quantity** - Enter the quantity
3. **Rate** - Enter the rate per unit
4. **Amount** - Auto-calculated (Quantity × Rate)
5. **Remarks** - Explain the budget or changes

**Pro Tips:**
- ✨ Amount is **automatically calculated** when you enter Quantity and Rate
- 📜 Previous budget values are shown in **yellow badges** for comparison
- ➕ Click **"Add"** button to add more budget line items
- ➖ Click **"Remove"** to delete a line item
- 🔒 You can add **unlimited budget lines** per project

#### Step 3: Status
- **Active** - Budget is active and visible in reports
- **Inactive** - Budget is saved but not active

#### Step 4: Save
Click the **"Insert"** or **"Update"** button at the bottom.

### 3. After Saving
You'll see a **success modal** with:
- ✅ Confirmation message
- 📊 Number of budget lines processed
- 🔄 Auto-redirect to budget list (3 seconds)

## 💡 Tips & Best Practices

### Creating Multiple Budgets for One Project
✅ **Allowed!** - You can create as many budgets as needed for a single project
- Use different Budget Titles and Sub Titles to organize them
- Example:
  - "Q1 2024 - Marketing Budget"
  - "Q2 2024 - Marketing Budget"
  - "Annual Marketing - Digital Ads"

### Budget History Tracking
Every time you update a budget:
- 📝 Previous values are saved automatically
- 📊 You can see the last entry when editing
- 🕐 Full audit trail maintained in BudgetLog table

### Using Auto-Calculation
1. Enter **Quantity** first
2. Enter **Rate** second
3. **Amount** updates automatically
4. You can still manually edit Amount if needed

### Deleting Budgets
⚠️ **Important**: When you delete a budget:
- All budget line items are deleted
- All budget history is deleted
- This action cannot be undone
- You'll see a confirmation dialog before deletion

## 📋 Common Workflows

### Creating a New Project Budget
1. Click **"+ Add New Budget"**
2. Enter Budget Title (e.g., "IT Infrastructure 2024")
3. Enter Sub Title (e.g., "Hardware & Software")
4. Select Project from dropdown
5. Add first budget line:
   - Select Head of Account (e.g., "Computer Equipment")
   - Enter Quantity (e.g., 10)
   - Enter Rate (e.g., 50000)
   - Amount auto-fills (500000)
   - Add Remarks (e.g., "Desktop computers for new office")
6. Click **"Add"** to add more lines
7. Repeat for all budget items
8. Set Status to **Active**
9. Click **"Insert"**

### Updating an Existing Budget
1. Find the budget in the table
2. Click the **Edit** icon
3. Modify any fields as needed
4. Notice the **yellow badge** showing previous values
5. Add remarks explaining the change
6. Click **"Update"**

### Viewing Budget Report
1. Click **"View BVE Report"** button
2. Select a Project from dropdown
3. See complete budget vs actual expense analysis

## 🎨 Visual Elements Guide

### Color Meanings
- **Purple Gradient** - Primary actions, headers
- **Green Gradient** - Success, Add actions
- **Red Gradient** - Delete, Remove actions
- **Blue Gradient** - Info, Reports
- **Yellow Badge** - Historical data, warnings

### Button Icons
- 🔄 **Refresh** - Reload data
- ➕ **Plus** - Add new item
- ✏️ **Pencil** - Edit item
- 🗑️ **Trash** - Delete item
- 📊 **Chart** - View report
- 💰 **Money** - Budget related
- 📝 **List** - Line items

## ⌨️ Keyboard Shortcuts
- **Tab** - Move between fields
- **Enter** - Submit form (when on submit button)
- **Esc** - Close modals

## 📱 Mobile Usage
The interface is fully responsive:
- Statistics cards stack vertically
- Table scrolls horizontally
- Input grids adapt to single column
- Touch-friendly buttons

## ❓ Troubleshooting

### "This Budget Title Already Exists"
- You've used this exact title before
- Change the Budget Title or Sub Title to make it unique

### Amount Not Calculating
- Make sure both Quantity and Rate are filled
- Values must be numbers
- Try clicking on the Amount field to trigger calculation

### Can't Delete Budget
- Make sure you click "OK" on the confirmation dialog
- Check if you have permission to delete

### Budget Not Showing in Report
- Make sure Status is set to **Active**
- Check if the budget is assigned to the correct Project
- Verify budget line items are saved

## 🔐 Permissions
(Varies by your system configuration)
- **View** - See budget list
- **Create** - Add new budgets
- **Edit** - Modify existing budgets  
- **Delete** - Remove budgets

---

**Need Help?** Contact your system administrator or refer to the MODERNIZATION_SUMMARY.md for technical details.
