<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Register']); ?>
	<body>
		<?php $this->load->view('nav'); ?>

		<div id="root"></div>

		<script type="text/babel">

			class Register extends React.Component {

				render() {

					function handleSubmit() {
						let username = document.getElementById('username').value;
						let email = document.getElementById('email').value;
						let password = document.getElementById('password').value;
						fetch('/auth/register', {
							method: 'POST',
							headers: {
								'Content-Type': 'application/x-www-form-urlencoded'
							},
							body: `username=${username}&email=${email}&password=${password}`
						})
						.then(response => response.json())
						.then(data => {
							if (data.status) {
								window.location.href = '<?= base_url('/auth/login') ?>';
							} else {
								alert(data.message);
							}
						});
					}

					return (
						<div className="container">
							<div className="d-flex flex-row justify-content-between">
								<div className="col-5 mt-5 my-3">
									<div role="form" className="card mt-5 mx-5 rounded-4 shadow border-0">
										<div className="card-header bg-white border-bottom-0 rounded-4">
											<h4 className="card-title text-secondary mx-2 mt-3 fw-bold">Register</h4>
										</div>
										<div className="card-body mx-2">
											<div className="form-floating mb-4">
												<input type="text" className="form-control rounded-3" id="username" name="username" placeholder="Username" aria-label="Username" required />
												<label htmlFor="username" className="form-label text-secondary">Username</label>
											</div>
											<div className="form-floating mb-4">
												<input type="email" className="form-control rounded-3" id="email" name="email" placeholder="Email" aria-label="Email" required />
												<label htmlFor="email" className="form-label text-secondary">Email</label>
											</div>
											<div className="form-floating mb-4">
												<input type="password" className="form-control" id="password" name="password" placeholder="Password" aria-label="Password" required />
												<label htmlFor="password" className="form-label text-secondary">Password</label>
											</div>
											<button type="button" className="btn btn-theme w-100 bg-gradient bg-theme border-0 shadow" onClick={handleSubmit}>Register</button>
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

			ReactDOM.render(<Register />, $('#root')[0]);

		</script>
	</body>
</html>
