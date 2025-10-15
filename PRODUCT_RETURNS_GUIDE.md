# Product Returns Guide

## How to Access Product Returns

### Method 1: From the Dashboard Sidebar
1. **Login** to your POS system
2. **Open the sidebar** by clicking the menu icon (☰) or pressing `Ctrl+B`
3. **Click on "Product Returns"** (icon: ↻) in the navigation menu
4. The Product Returns page will load

### Method 2: Direct URL
- Navigate to: `http://your-domain.com/profile/returns`

## How to Process a Return

### Step 1: Find the Sale
In the "Find Sale for Return" section, you can search by:
- **Sale ID / Receipt Number**: Enter the transaction code (e.g., TXN-123456)
- **Customer Phone**: Search by customer's phone number
- **Date Range**: Filter by sale date

Click on any sale in the search results to proceed.

### Step 2: Select Items to Return
1. **Check the boxes** next to items you want to return
2. **Enter quantity** to return for each item (max = quantity sold)
3. Items show:
   - Product name and SKU
   - Quantity sold
   - Price per item

### Step 3: Provide Return Details
Fill in the required information:

**Return Reason** (required):
- Defective Product
- Wrong Item
- Customer Changed Mind
- Damaged in Shipping
- Not as Described
- Duplicate Order
- Other

**Refund Method** (required):
- Original Payment Method
- Cash
- Store Credit
- Exchange Only

**Notes** (optional):
- Add any additional details about the return

### Step 4: Review and Process
1. Review the **Return Summary**:
   - Items to return count
   - Total refund amount
2. Click **"Process Return"** button
3. Confirm the return in the popup dialog

## What Happens When You Process a Return

The system automatically:
1. ✅ **Creates a return record** (RET-XXXXXX)
2. ✅ **Restores inventory** to the store
3. ✅ **Creates a refund payment** (negative transaction)
4. ✅ **Updates sale status**:
   - `partially_refunded` if some items returned
   - `refunded` if all items returned
5. ✅ **Creates stock movement** for audit trail

## Recent Returns

The "Recent Returns" table shows:
- Return ID
- Original Sale ID
- Customer name
- Items count
- Refund amount
- Return reason
- Date processed

## API Endpoints (For Developers)

### List Returns
```
GET /profile/api/returns?per_page=20
GET /api/returns?per_page=20
```

### Process Return
```
POST /profile/api/returns
POST /api/returns
```

Request Body:
```json
{
  "sale_id": 123,
  "reason": "defective",
  "refund_method": "cash",
  "notes": "Product was damaged",
  "items": [
    {
      "sale_item_id": 456,
      "quantity": 2,
      "condition_notes": "Box damaged"
    }
  ]
}
```

### View Return Details
```
GET /profile/api/returns/{id}
GET /api/returns/{id}
```

### Update Return Status
```
PUT /profile/api/returns/{id}
```

### Delete Pending Return
```
DELETE /profile/api/returns/{id}
```

## Validation Rules

- Cannot return more items than were purchased
- Sale must exist and be completed
- Return reason is required
- Refund method is required
- At least one item must be selected for return
- Only pending returns can be deleted

## Permissions

- Authenticated users can process returns
- Returns are tracked by the user who processed them

## Testing

Run tests with:
```bash
php artisan test --filter=ProductReturnTest
```

All 10 tests should pass:
- List returns
- Show specific return
- Process return successfully
- Validate return data
- Prevent returning more than purchased
- Update sale status (refunded/partially_refunded)
- Update return status
- Delete permissions

## Troubleshooting

### Can't see Product Returns page?
1. Make sure you're logged in
2. Check if the sidebar is expanded (click ☰ or press Ctrl+B)
3. Look for "Product Returns" with the ↻ icon
4. Try navigating directly to `/profile/returns`

### API returns 404?
- Make sure migrations were run: `php artisan migrate`
- Check routes are registered in `routes/web.php` and `routes/api.php`

### No sales appearing in search?
- Make sure you have completed sales in the database
- Only completed sales can be returned
- Check the sale status is `completed`

## Database Tables

### `returns` table
- Stores main return information
- Tracks refund amounts, reason, method, status
- Links to sale, store, customer, and processing user

### `return_items` table
- Individual items being returned
- Tracks quantities and prices
- Links to sale_item and product

## Features

- ✅ Full return processing with inventory restoration
- ✅ Automatic payment refund records
- ✅ Sale status updates (refunded/partially_refunded)
- ✅ Stock movement audit trail
- ✅ Multiple refund methods
- ✅ Comprehensive validation
- ✅ Full CRUD API
- ✅ Extensive test coverage
