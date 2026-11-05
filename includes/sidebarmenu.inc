<nav id="mainsidebar" class="d-flex flex-column flex-shrink-0 p-3 bg-light">
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
      <li class="nav-item">
        <a href="index.php?link=Stocks" id="StocksNav" class="nav-link link-dark">
          <svg class="bi me-2" width="16" height="16"><use xlink:href="#home"/></svg>
          Stock Item
        </a>
      </li>
      
      <li class="nav-item">
        <a href="index.php?link=stockitemcategories" id="stockitemcategoriesNav" class="nav-link link-dark">
          <svg class="bi me-2" width="16" height="16"><use xlink:href="#home"/></svg>
          Stock Category
        </a>
      </li>
      <li>
        <a href="index.php?link=unitsofmesure" id="unitsofmesureNav" class="nav-link link-dark">
          <svg class="bi me-2" width="16" height="16"><use xlink:href="#speedometer2"/></svg>
          Units of measure
        </a>
      </li>
  <li>
        <a href="index.php?link=CompanyPreferences" id="CompanyPreferencesNav" class="nav-link link-dark">
          <svg class="bi me-2" width="16" height="16"><use xlink:href="#speedometer2"/></svg>
          Company Preferences
        </a>
      </li>
      <li>
        <a href="index.php?link=SystemParameters" id="SystemParametersNav" class="nav-link link-dark">
          <svg class="bi me-2" width="16" height="16"><use xlink:href="#speedometer2"/></svg>
          System Parameters
        </a>
      </li>
    </ul>
    <hr>
    
<ul class="navbar-nav">
    <li class="nav-item"><a class="nav-link" aria-current="page" href="https://www.team-software.africa">
	<?php echo 'POS ERP  © 2012 - ' . Date('Y');?>
</a></li><li class="nav-item">
	<?php echo DisplayDateTime(); ?>
</li>
</ul>
</nav>
