<?php $page = isset($page) ? $page : ''; ?>
<div id="nav"></div>

<script type="text/babel">

	class NavBar extends React.Component {

		render() {

			const loggedIn = window.localStorage.getItem('token') !== null;

			return (
				<>
					<div className="navbar navbar-expand-lg navbar-dark bg-gradient bg-theme">
						<div className="container">
							<a className="navbar-brand" href="/">Logo</a>
						</div>
					</div>
					<div className="navbar navbar-expand-lg navbar-dark bg-gradient bg-theme bg-opacity-50 py-0">
						<div className="container">
							<div className="navbar-nav w-100 d-flex justify-content-between">
								<a className={"nav-link ps-0"} href="/">Home</a>
								{
									loggedIn ?
										<div className="d-flex flex-row">
											<a className="nav-link" href="/auth/logout">Logout</a>
										</div> :
										<div className="d-flex flex-row gap-2">
											<a className="nav-link" href="/auth/register">Register</a>
											<a className="nav-link" href="/auth/login">Login</a>
										</div>
								}
							</div>
						</div>
					</div>
				</>
			);
		}
	}

	ReactDOM.render(<NavBar />, $('#nav')[0]);

</script>
