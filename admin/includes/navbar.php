<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-store me-2"></i>
            Admin Panel
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>" 
                       href="index.php">
                        <i class="fas fa-tachometer-alt me-1"></i>
                        Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'products' ? 'active' : ''; ?>" 
                       href="products.php">
                        <i class="fas fa-box me-1"></i>
                        Products
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'categories' ? 'active' : ''; ?>" 
                       href="categories.php">
                        <i class="fas fa-folder me-1"></i>
                        Categories
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'orders' ? 'active' : ''; ?>" 
                       href="orders.php">
                        <i class="fas fa-shopping-cart me-1"></i>
                        Orders
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>" 
                       href="users.php">
                        <i class="fas fa-users me-1"></i>
                        Users
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" 
                       href="#" 
                       role="button" 
                       data-bs-toggle="dropdown" 
                       aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Make sure Bootstrap JS is loaded properly -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

<style>
body {
    padding-top: 70px; /* Adjust for fixed navbar */
}

.navbar {
    padding: 0.5rem 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.navbar-brand {
    font-weight: 600;
    font-size: 1.25rem;
}

.nav-link {
    padding: 0.8rem 1rem !important;
    font-weight: 500;
}

.nav-link.active {
    background-color: rgba(255,255,255,.1);
}

.navbar-dark .navbar-nav .nav-link {
    color: rgba(255,255,255,.8);
}

.navbar-dark .navbar-nav .nav-link:hover {
    color: #fff;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
}

.dropdown-item {
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item.text-danger:hover {
    background-color: #dc3545;
    color: white !important;
}

@media (max-width: 991.98px) {
    .navbar-nav {
        padding: 0.5rem 0;
    }
    
    .nav-link {
        padding: 0.5rem 1rem !important;
    }
    
    .dropdown-menu {
        box-shadow: none;
        border: none;
        padding: 0;
        margin: 0;
    }
    
    .dropdown-item {
        padding: 0.5rem 1.5rem;
    }
}

/* Add specific dropdown styles */
.dropdown-menu {
    margin-top: 0.5rem;
    border-radius: 0.375rem;
}

.dropdown-toggle::after {
    vertical-align: middle;
}

/* Ensure dropdown works on hover for desktop */
@media (min-width: 992px) {
    .dropdown:hover .dropdown-menu {
        display: block;
    }
}
</style>

<script>
// Initialize all dropdowns
document.addEventListener('DOMContentLoaded', function() {
    var dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(function(dropdown) {
        new bootstrap.Dropdown(dropdown);
    });
});
</script> 