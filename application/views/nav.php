<?php $page = isset($page) ? $page : ''; ?>
<div class="navbar navbar-expand-lg navbar-dark bg-gradient bg-theme">
	<div class="container">
		<a class="navbar-brand" href="/">Logo</a>
	</div>
</div>
<div class="navbar navbar-expand-lg navbar-dark bg-gradient bg-theme bg-opacity-50 py-0">
	<div class="container">
		<div class="navbar-nav w-100 d-flex justify-content-between">
			<a class="nav-link ps-0 <?php if ($page == 'home') echo 'active' ?>" href="/">Home</a>
			<script>
				if (loggedIn) {
					document.write(`
					<div class="d-flex flex-row">
						<a class="nav-link" href="/auth/logout">Logout</a>
					</div>`)
				} else {
					document.write(`
					<div class="d-flex flex-row gap-2">
						<a class="nav-link <?php if ($page == 'register') echo 'active' ?>" href="/auth/register">Register</a>
						<a class="nav-link <?php if ($page == 'login') echo 'active' ?>" href="/auth/login">Login</a>
					</div>`)
				}
			</script>
		</div>
	</div>
</div>
