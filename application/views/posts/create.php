<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Home']); ?>
	<body>
		<?php $this->load->view('nav', ['page' => 'home']); ?>
		<div class="container mt-5 mb-5">
			<div role="form" class="card mt-5 mx-5 rounded-4 shadow border-0">
				<div class="card-header bg-white border-bottom-0 rounded-4">
					<h4 class="card-title text-secondary mx-2 mt-3 fw-bold">Create a Post</h4>
				</div>
				<div class="card-body mx-2">
					<div class="d-flex flex-row">
						<div class="col-9">
							<div class="me-4">
								<div class="d-flex flex-row">
									<div class="col-6">
										<div class="form-floating mb-4 me-2">
											<select id="category" class="form-select" aria-label="Select post category" required>
											</select>
											<label for="category" class="form-label text-secondary">Category</label>
										</div>
									</div>
									<div class="col-6">
										<div class="mb-4 ms-2"></div>
									</div>
								</div>
								<div class="d-flex flex-row">
									<div class="col-6">
										<div class="form-floating mb-4 me-2">
											<input id="tags" class="form-control" aria-label="Tags" placeholder="Tags">
											<label for="tags" class="form-label text-secondary">Tags</label>
										</div>
									</div>
									<div class="col-6">
										<div class="form-floating mb-4 ms-2">
											<input id="keywords" class="form-control" aria-label="Keywords" placeholder="Keywords">
											<label for="keywords" class="form-label text-secondary">Keywords</label>
										</div>
									</div>
								</div>
								<div class="form-floating col-12 mb-4">
									<input id="title" class="form-control" aria-label="Title" placeholder="Title">
									<label for="title" class="form-label text-secondary">Title</label>
								</div>
								<div class="form-floating col-12 mb-4">
									<textarea id="content" class="form-control" aria-label="Content" placeholder="Content" style="resize: none; min-height: 98px"></textarea>
									<label for="content" class="form-label text-secondary">Content</label>
								</div>
							</div>
						</div>
						<div class="col-3">
							<div class="ms-4">
								<p class="text-secondary fw-bold">Attachments</p>
								<div id="attachments-block" class="d-flex flex-column overflow-auto pe-3 gap-3">
									<!-- outlined box -->
									<div id="attachment-0" class="col-12 rounded-3" style="min-height: 70px; background-color: #f9f9f9">
										<div class="d-flex flex-row">
											<div class="col-9">
												<div class="m-2">
													<p class="text-start text-dark text-truncate mb-0">template.pdf</p>
													<p class="text-start text-secondary mb-0">1.2 MB</p>
												</div>
											</div>
											<div class="col-3">
												<div class="float-end">
													<button class="btn">
														<i class="fa-solid fa-xmark fa-2xs bg-transparent"></i>
													</button>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<button id="upload" class="btn btn-theme me-2">Upload Attachment</button>
					<button id="submit" class="btn btn-theme">Create</button>
				</div>
			</div>
		</div>
		<script>

			function loadCategories(token) {
				$.ajax({
					url: '<?= base_url('/posts/categories') ?>',
					type: 'GET',
					headers: {
						'X-Token': token
					},
					success: function(data) {
						data = $.parseJSON(data);
						let categorySelect = $('select#category');
						data.forEach(function(category) {
							categorySelect.append(`<option value="${category.id}">${category.name}</option>`);
						});
					},
					error: function(response) {
						if (response.status === 401) {
							localStorage.removeItem('token');
							window.location.href = '<?= base_url('/auth/login') ?>';
						}
					}
				});
			}

			function loadInputs() {
				$('#content').on('input', function() {
					$(this).css('height', 'auto');
					$(this).height(this.scrollHeight - 16);
					$('#attachments-block').css('max-height', `${this.scrollHeight + 258}px`);
				});
			}

			$(document).ready(function() {
				const localStorage = window.localStorage;
				const token = localStorage.getItem('token');
				if (!token) {
					window.location.href = '<?= base_url('/auth/login') ?>';
					return;
				}
				loadCategories(token);
				loadInputs();
			});
		</script>
	</body>
</html>
