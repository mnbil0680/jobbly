# Debugging Card Click Issue

## Current Status

Scripts are loading correctly:
- ✅ test_endpoints.js loaded (line 335)
- ✅ test_endpoint_details.js loaded (line 652)
- ✅ Test endpoints page loading (line 17)

## What I Fixed

### Problem
Cards were created but click events weren't firing properly.

### Solution Applied

1. **Event Delegation** - Added click handler to the parent grid instead of individual cards
   - More reliable than attaching to each card
   - Works even if cards are dynamically created

2. **Simplified Card Creation** - Removed inline onclick to avoid conflicts
   - Cards now only have data attributes
   - Grid handles all click events

3. **Enhanced Logging** - Added detailed console messages at every step

## Expected Console Output (After Fix)

When you click a card, you should see:

```
=== Grid click delegation ===
Clicked card source ID: adzuna
=== showSourceDetails called ===
Source ID: adzuna
Hidden test endpoints container
Shown details container
Loading source details for: adzuna
```

## Testing Steps

1. **Open Application**
   ```
   http://localhost/jobbly/app/
   ```

2. **Open Browser Console** (F12)

3. **Click "Test Endpoints" Tab**
   - Should see: `Loading test endpoints page...`
   - Should see: `=== Displaying X source cards ===`
   - Should see: `Card 1: Adzuna (ID: adzuna)`
   - Should see: `Event delegation added to sources grid`

4. **Click Any Card**
   - Should see: `=== Grid click delegation ===`
   - Should see: `Clicked card source ID: [id]`
   - Should navigate to details page

## If Still Not Working

### Check 1: Verify Data Attributes
In console, run:
```javascript
document.querySelectorAll('.source-card').forEach(card => {
    console.log(card.getAttribute('data-source-id'), card.getAttribute('data-source-name'));
});
```
Should output all source IDs and names.

### Check 2: Verify Grid Exists
In console, run:
```javascript
console.log('Grid element:', document.getElementById('sourcesGrid'));
console.log('Grid onclick:', document.getElementById('sourcesGrid').onclick);
```
Should show the grid element and its onclick function.

### Check 3: Manual Test
In console, run:
```javascript
showSourceDetails('adzuna');
```
Should navigate to details page for Adzuna.

### Check 4: Check for CSS Issues
Make sure cards are visible and clickable:
```javascript
const cards = document.querySelectorAll('.source-card');
cards.forEach(card => {
    console.log('Card:', card);
    console.log('Computed style:', window.getComputedStyle(card).cursor);
    console.log('Pointer events:', window.getComputedStyle(card).pointerEvents);
});
```

## Files Modified

1. **app/assets/js/test_endpoints.js**
   - Line 115-142: `displaySourceCards()` - Added event delegation
   - Line 147-220: `createSourceCard()` - Simplified, removed inline onclick
   - Line 272-304: `showSourceDetails()` - Enhanced with logging

## Quick Fix Test

If cards still don't click, try this manual fix in browser console:

```javascript
// Remove old handler
const grid = document.getElementById('sourcesGrid');
grid.onclick = null;

// Add new handler
grid.addEventListener('click', function(e) {
    const card = e.target.closest('.source-card');
    if (card && card.getAttribute('data-source-id')) {
        console.log('Card clicked!', card.getAttribute('data-source-id'));
        showSourceDetails(card.getAttribute('data-source-id'));
    }
});
```

Then click a card - it should work!
