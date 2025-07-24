## Nsikacart Backend Setup Instructions

1. Clone the repository
2. Copy `api/config/db.example.php` to `api/config/db.php`
3. Update database credentials in `database.php`
4. Create uploads directory: `mkdir public/dashboard/uploads`
5. Set proper permissions on uploads directory

## Product Delete Design Decisions \& Explanations

### 1\. Modal Confirmation Pattern

Why: Prevents accidental deletions by requiring explicit user confirmation

How: Uses the same modal pattern as checkout for consistency

Benefit: Familiar UX reduces cognitive load

#### 2\. Database Transaction

Why: Ensures data integrity - if file deletion fails, database rollback prevents orphaned records

How: Uses PDO transactions to wrap database and file operations

Benefit: Prevents inconsistent state between database and filesystem

#### 3\. User Authorization Check

Why: Security - users should only delete their own products

How: Verifies product ownership before deletion using user\_id from session

Benefit: Prevents unauthorized access and data manipulation

#### 4\. File Cleanup

Why: Prevents storage bloat from orphaned image files

How: Deletes associated images from filesystem after database deletion

Benefit: Maintains clean storage and prevents disk space issues

#### 5\. Error Handling \& User Feedback

Why: Users need to know what happened (success/failure)

How: Toast notifications provide immediate feedback

Benefit: Clear communication reduces user confusion

#### 6\. Table Refresh After Deletion

Why: UI should reflect current data state

How: Automatically refreshes the products table after successful deletion

Benefit: Immediate visual confirmation of the action

#### 7\. Event Delegation

Why: Dynamic content requires proper event handling

How: Sets up event listeners after table rendering

Benefit: Works with dynamically generated delete buttons

#### 8\. Graceful Error Recovery

Why: Network/server issues shouldn't break the UI

How: Try-catch blocks with user-friendly error messages

Benefit: Better user experience during edge cases

***This implementation provides a secure, user-friendly deletion system that follows best practices for data integrity and user experience.***


## How the permission system works:

## **Admin Permissions:**

* ✅ View all users
* ✅ Suspend/activate any user (except themselves)
* ✅ Delete any user (except themselves)
* ✅ Change any user's role (except their own)

## **Monitor Permissions:**

* ✅ View all users
* ✅ Suspend/activate regular users and other monitors (except themselves)
* ❌ Cannot suspend/activate admins
* ❌ Cannot delete any users
* ❌ Cannot change user roles

## **Regular User Permissions:**

* ❌ Cannot access user management at all
