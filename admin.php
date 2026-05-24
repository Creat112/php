<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .nav-buttons {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-success {
            background-color: #28a745;
            color: white;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .data-table tr:hover {
            background-color: #f5f5f5;
        }
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background: white;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .hidden {
            display: none;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div id="loginSection" class="login-form">
        <h2>Admin Login</h2>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div id="loginError" style="color: red; margin-top: 10px; display: none;"></div>
    </div>

    <div id="adminPanel" class="container hidden">
        <div class="header">
            <h1>Admin Panel</h1>
            <div class="nav-buttons">
                <button onclick="refreshData()" class="btn btn-primary">Refresh</button>
                <button onclick="logout()" class="btn btn-danger">Logout</button>
            </div>
        </div>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number" id="totalUsers">-</div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalProducts">-</div>
                <div class="stat-label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="totalOrders">-</div>
                <div class="stat-label">Total Orders</div>
            </div>
        </div>

        <div class="section">
            <h2>Users Management</h2>
            <button onclick="loadUsers()" class="btn btn-success">Load Users</button>
            <table id="usersTable" class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="section">
            <h2>Products Management</h2>
            <button onclick="loadProducts()" class="btn btn-success">Load Products</button>
            <table id="productsTable" class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script>
        const API_BASE = '/backend/api/';

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const loginData = {
                email: 'admin@example.com',
                password: 'admin123'
            };

            try {
                const response = await fetch(API_BASE + 'login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(loginData)
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    document.getElementById('loginSection').classList.add('hidden');
                    document.getElementById('adminPanel').classList.remove('hidden');
                    loadDashboard();
                } else {
                    document.getElementById('loginError').textContent = result.message || 'Login failed';
                    document.getElementById('loginError').style.display = 'block';
                }
            } catch (error) {
                document.getElementById('loginError').textContent = 'Network error. Please try again.';
                document.getElementById('loginError').style.display = 'block';
            }
        });

        async function loadDashboard() {
            await Promise.all([
                loadUsers(),
                loadProducts(),
                updateStats()
            ]);
        }

        async function loadUsers() {
            try {
                const response = await fetch(API_BASE + 'getUsers.php');
                const users = await response.json();
                
                const tbody = document.querySelector('#usersTable tbody');
                tbody.innerHTML = '';
                
                users.forEach(user => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td>${user.id || '-'}</td>
                        <td>${user.name || '-'}</td>
                        <td>${user.email || '-'}</td>
                        <td>
                            <button onclick="editUser(${user.id})" class="btn btn-primary">Edit</button>
                            <button onclick="deleteUser(${user.id})" class="btn btn-danger">Delete</button>
                        </td>
                    `;
                });
                
                document.getElementById('totalUsers').textContent = users.length;
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }

        async function loadProducts() {
            try {
                const response = await fetch(API_BASE + 'getProducts.php');
                const products = await response.json();
                
                const tbody = document.querySelector('#productsTable tbody');
                tbody.innerHTML = '';
                
                products.forEach(product => {
                    const row = tbody.insertRow();
                    row.innerHTML = `
                        <td>${product.id || '-'}</td>
                        <td>${product.name || '-'}</td>
                        <td>$${product.price || '0'}</td>
                        <td>${product.stock || '0'}</td>
                        <td>
                            <button onclick="editProduct(${product.id})" class="btn btn-primary">Edit</button>
                            <button onclick="deleteProduct(${product.id})" class="btn btn-danger">Delete</button>
                        </td>
                    `;
                });
                
                document.getElementById('totalProducts').textContent = products.length;
            } catch (error) {
                console.error('Error loading products:', error);
            }
        }

        async function updateStats() {
            try {
                // You can add more API calls for orders stats
                document.getElementById('totalOrders').textContent = '-';
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }

        function refreshData() {
            loadDashboard();
        }

        function logout() {
            document.getElementById('loginSection').classList.remove('hidden');
            document.getElementById('adminPanel').classList.add('hidden');
            document.getElementById('loginForm').reset();
            document.getElementById('loginError').style.display = 'none';
        }

        function editUser(id) {
            alert('Edit user functionality would go here for user ID: ' + id);
        }

        function deleteUser(id) {
            if (confirm('Are you sure you want to delete this user?')) {
                alert('Delete user functionality would go here for user ID: ' + id);
            }
        }

        function editProduct(id) {
            alert('Edit product functionality would go here for product ID: ' + id);
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                alert('Delete product functionality would go here for product ID: ' + id);
            }
        }
    </script>
</body>
</html>
