# Database Seeder - Data Testing

Database telah di-seed dengan data lengkap untuk testing sistem kasir cafe multi-lokasi.

## 🔐 User Credentials

### Owner

-   **Email**: `owner@cafepro.com`
-   **Password**: `password`
-   **PIN**: `111111`
-   **Role**: Owner

### Manager Senopati

-   **Email**: `manager.senopati@cafepro.com`
-   **Password**: `password`
-   **PIN**: `222222`
-   **Role**: Manajer Cabang
-   **Branch**: Senopati

### Kasir Senopati 1

-   **Email**: `kasir1.senopati@cafepro.com`
-   **Password**: `password`
-   **PIN**: `123456`
-   **Role**: Kasir
-   **Branch**: Senopati

### Kasir Senopati 2

-   **Email**: `kasir2.senopati@cafepro.com`
-   **Password**: `password`
-   **PIN**: `234567`
-   **Role**: Kasir
-   **Branch**: Senopati

### Kasir Kemang 1

-   **Email**: `kasir1.kemang@cafepro.com`
-   **Password**: `password`
-   **PIN**: `345678`
-   **Role**: Kasir
-   **Branch**: Kemang

### Staff Gudang Senopati

-   **Email**: `gudang.senopati@cafepro.com`
-   **Password**: `password`
-   **PIN**: `456789`
-   **Role**: Staff Gudang
-   **Branch**: Senopati

---

## 🏢 Branches

1. **Cabang Senopati** (SNP)

    - Jl. Senopati No. 123, Jakarta Selatan
    - 08:00 - 22:00

2. **Cabang Kemang** (KMG)

    - Jl. Kemang Raya No. 45, Jakarta Selatan
    - 09:00 - 23:00

3. **Cabang BSD** (BSD)
    - BSD City, Tangerang Selatan
    - 10:00 - 22:00

---

## 🏪 Suppliers

1. **PT Kopi Nusantara** (SUP001)
2. **CV Susu Segar** (SUP002)
3. **Toko Gula Manis** (SUP003)

---

## 📦 Raw Materials (8 items)

1. Kopi Arabica (RM001) - Rp 150/gram
2. Kopi Robusta (RM002) - Rp 100/gram
3. Susu Full Cream (RM003) - Rp 20/ml
4. Gula Pasir (RM004) - Rp 12/gram
5. Coklat Bubuk (RM005) - Rp 80/gram
6. Whipped Cream (RM006) - Rp 50/ml
7. Sirup Vanila (RM007) - Rp 40/ml
8. Sirup Hazelnut (RM008) - Rp 45/ml

---

## 📋 Recipes (4 recipes)

1. **Espresso** - 18g Arabica
2. **Cappuccino** - 18g Arabica + 150ml Susu + 10g Gula
3. **Cafe Latte** - 18g Arabica + 200ml Susu + 10g Gula
4. **Mocha** - 18g Arabica + 150ml Susu + 20g Coklat + 30ml Whipped Cream

---

## 📱 Menu Categories & Items

### Espresso Based (ESP)

1. **Espresso** - Rp 18,000
2. **Cappuccino** - Rp 28,000
3. **Cafe Latte** - Rp 30,000
4. **Mocha** - Rp 35,000

### Coffee (COF)

5. **Americano** - Rp 22,000

### Non Coffee (NCF)

6. **Hot Chocolate** - Rp 25,000

### Snacks (SNK)

7. **Croissant** - Rp 20,000

---

## 📦 Stock Status

### Cabang Senopati (well-stocked)

-   Kopi Arabica: 10,000 gram
-   Kopi Robusta: 7,500 gram
-   Susu: 20,000 ml
-   Gula: 10,000 gram
-   Others: 5,000 units each

### Cabang Kemang (moderate stock)

-   All materials: 3,000 units each

---

## 🧪 Testing Scenarios

### 1. Test Login dengan Email

```json
POST /api/login
{
  "email": "kasir1.senopati@cafepro.com",
  "password": "password"
}
```

### 2. Test Login dengan PIN

```json
POST /api/login/pin
{
  "pin": "123456",
  "branch_id": 1
}
```

### 3. Test Create Order

```json
POST /api/orders
{
  "branch_id": 1,
  "order_type": "dine_in",
  "items": [
    {
      "menu_id": 1,
      "quantity": 2,
      "unit_price": 18000
    },
    {
      "menu_id": 2,
      "quantity": 1,
      "unit_price": 28000
    }
  ]
}
```

### 4. Test Reports

-   Sales Report: `GET /api/reports/sales?start_date=2025-01-01&end_date=2025-12-31&branch_id=1`
-   Low Stock: `GET /api/reports/low-stock`
-   Financial: `GET /api/reports/financial?month=1&year=2025`

---

## 🔄 Reset Database

Untuk reset dan seed ulang database:

```bash
php artisan migrate:fresh --seed
```

---

## ✅ What's Seeded

-   ✅ 4 Roles (owner, manajer_cabang, kasir, staff_gudang)
-   ✅ 6 Users dengan PIN
-   ✅ 5 Employees
-   ✅ 3 Branches
-   ✅ 3 Suppliers
-   ✅ 8 Raw Materials
-   ✅ 2 Purchase Orders (received)
-   ✅ Stock untuk 2 cabang
-   ✅ 4 Recipes dengan items
-   ✅ 4 Menu Categories
-   ✅ 7 Menu Items

---

**Happy Testing! 🎉**
