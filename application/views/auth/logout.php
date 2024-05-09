<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Logout']); ?>
	<body>
		<?php $this->load->view('nav'); ?>
		<div id="root"></div>
	</body>

	<script type="text/babel">

		class Logout extends React.Component {

			componentDidMount() {
				// Remove the token from localStorage
				const localStorage = window.localStorage;
				localStorage.removeItem('token');
				// Redirect to the login page
				window.location.href = '<?= base_url('/auth/login') ?>';
			}

			render() {
				return (
					<></>
				);
			}
		}

		ReactDOM.render(<Logout />, $('#root')[0]);

	</script>

</html>
