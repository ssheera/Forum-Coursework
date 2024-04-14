<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Logout']); ?>
	<body>
		<?php $this->load->view('nav'); ?>
	</body>

	<script>
		$(document).ready(function() {
			let localStorage = window.localStorage;
			localStorage.removeItem('token');
			window.location.href = '<?= base_url('/auth/login') ?>';
		});
	</script>

</html>
