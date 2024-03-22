<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Login']); ?>
	<body>
		<?php $this->load->view('nav', ['page' => 'login']); ?>
		<div class="container">
			<div class="d-flex flex-row justify-content-between">
				<div class="col-5 mt-5 my-3">
					<div role="form" class="card mt-5 mx-5 rounded-4 shadow border-0">
						<div class="card-header bg-white border-bottom-0 rounded-4">
							<h4 class="card-title text-secondary mx-2 mt-3 fw-bold">Login</h4>
						</div>
						<div class="card-body mx-2">
							<div class="form-floating mb-4">
								<input type="text" class="form-control rounded-3" id="username" name="username" placeholder="Username" required>
								<label for="username" class="form-label text-secondary">Username</label>
							</div>
							<div class="form-floating mb-4">
								<input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
								<label for="password" class="form-label text-secondary">Password</label>
							</div>
							<button id="login-button" type="submit" class="btn btn-theme w-100 bg-gradient bg-theme border-0 shadow">Login</button>
						</div>
					</div>
				</div>
				<div class="col-5 mt-5 my-3">
					<img src="https://raw.githubusercontent.com/ssheera/images/main/banner.png" class="img-fluid rounded-2" alt="Banner">
				</div>
			</div>
		</div>
	</body>

	<script>
		$(document).ready(function() {
			$('#login-button').click(function() {
				let username = $('#username').val();
				let password = $('#password').val();

				$.ajax({
					url: '<?= '/auth/login' ?>',
					type: 'POST',
					contentType: 'application/x-www-form-urlencoded',
					data: {
						username: username,
						password: password
					},
					success: function(response) {
						response = $.parseJSON(response);
						if (response.status) {
							let localStorage = window.localStorage;
							localStorage.setItem('token', response.token);
							window.location.href = '<?= base_url('/') ?>';
						} else {
							alert(response.message);
						}
					}
				});
			});
		});
	</script>

</html>
