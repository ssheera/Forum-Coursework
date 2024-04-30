<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Login']); ?>
	<body>
		<?php $this->load->view('nav'); ?>
		<div id="root"></div>
	</body>

	<script type="text/babel">

		class Login extends React.Component {

			render() {

				function handleSubmit() {
					const username = $('#username').val();
					const password = $('#password').val();
					$.ajax({
						url: '/auth/login',
						type: 'POST',
						contentType: 'application/x-www-form-urlencoded',
						data: {
							username: username,
							password: password
						},
						success: function(response) {
							response = $.parseJSON(response);
							if (response.status) {
								const localStorage = window.localStorage;
								localStorage.setItem('token', response.token);
								window.location.href = '<?= base_url('/') ?>';
							} else {
								alert(response.message);
							}
						}
					});
				}

				return (
					<div className="container">
						<div className="d-flex flex-row justify-content-between">
							<div className="col-5 mt-5 my-3">
								<div role="form" className="card mt-5 mx-5 rounded-4 shadow border-0">
									<div className="card-header bg-white border-bottom-0 rounded-4">
										<h4 className="card-title text-secondary mx-2 mt-3 fw-bold">Login</h4>
									</div>
									<div className="card-body mx-2">
										<div className="form-floating mb-4">
											<input type="text" className="form-control rounded-3" id="username" name="username" placeholder="Username" aria-label="Username" required />
											<label htmlFor="username" className="form-label text-secondary">Username</label>
										</div>
										<div className="form-floating mb-4">
											<input type="password" className="form-control" id="password" name="password" placeholder="Password" aria-label="Password" required />
											<label htmlFor="password" className="form-label text-secondary">Password</label>
										</div>
										<button type="submit" className="btn btn-theme w-100 bg-gradient bg-theme border-0 shadow" onClick={handleSubmit}>Login</button>
									</div>
								</div>
							</div>
							<div className="col-5 mt-5 my-3">
								<img src="https://raw.githubusercontent.com/ssheera/images/main/banner.png" className="img-fluid rounded-2" alt="Banner" />
							</div>
						</div>
					</div>
				)

			}
		}

		ReactDOM.render(<Login />, $('#root')[0]);

	</script>

</html>
