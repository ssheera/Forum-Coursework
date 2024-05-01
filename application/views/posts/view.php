<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Viewing Post']); ?>
	<body>
		<?php $this->load->view('nav'); ?>

		<div id="root"></div>

		<script>
			if (localStorage.getItem('token')) {
				document.write(`
				<div class="container d-flex flex-row gap-4">
					<a href="<?= base_url('/posts/author/self') ?>" style="text-decoration: underline; text-underline-offset: 3px" class="nav-link text-dark mt-3">Your Posts</a>
					<a href="<?= base_url('/posts/create') ?>" style="text-decoration: underline; text-underline-offset: 3px" class="nav-link text-dark mt-3">Create Post</a>
				</div>`);
			}
		</script>
		<div class="container mt-4">
			<div class="d-flex flex-row float-end border-0">
				<div style="width: 30%"></div>
				<label for="searchBox" class="form-label visually-hidden">Search</label>
				<input id="searchBox" class="form-control border-0 bg-secondary bg-opacity-25 ps-3 pe-1 py-1 rounded-3 rounded-end-0" placeholder="Search">
				<button id="searchButton" class="px-2 py-0 m-0 btn btn-light border-0 rounded-3 rounded-start-0"><i class="fas fa-search"></i></button>
			</div>
		</div>

		<div class="container mt-5 mb-5">
			<div id="posts" class="container m-2 mx-auto" style="width: 70rem">
				<div id="post" class="visually-hidden">
					<div class="shadow rounded-2">
						<div class="container d-flex rounded-top justify-content-between" style="background: #F0F0F0">
							<h6 id="title" class="text-dark text-capitalize py-2 m-0"></h6>
							<p id="parentThread" class="visually-hidden text-dark text-capitalize fst-italic float-end py-2 m-0" style="font-size: 0.8rem">Parent Thread</p>
						</div>
						<div class="container">
							<p id="content" class="text-dark p-3 m-0" style="white-space: pre-line"></p>
						</div>

						<div id="attachment" class="container d-flex visually-hidden">
							<p id="name" class="m-0 p-3 text-primary" style="cursor: pointer"></p>
						</div>

					</div>
					<div class="mx-2 d-flex justify-content-between">
						<div class="d-flex gap-3">
							<p id="edit" class="nav-link text-dark mt-2" style="cursor: pointer; font-size: 0.9rem">Edit</p>
							<p id="delete" class="nav-link text-dark mt-2" style="cursor: pointer; font-size: 0.9rem">Delete</p>
							<p id="reply" class="nav-link text-dark mt-2" style="cursor: pointer; font-size: 0.9rem">Reply</p>
						</div>
						<div class="d-flex flex-column text-end">
							<p id="updated" class="text-dark m-0 mt-2" style="font-size: 0.9rem">2024-01-01</p>
							<p id="author" class="text-dark m-0" style="font-size: 0.9rem">admin</p>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script>

			function formatMemory(bytes) {
				const b = 1024;
				const kb = b * 1024;
				const mb = kb * 1024;
				const gb = mb * 1024;
				const tb = gb * 1024;
				if (bytes < b) return bytes + ' B';
				if (bytes < kb) return (bytes / b).toFixed(2) + ' KB';
				if (bytes < mb) return (bytes / kb).toFixed(2) + ' MB';
				if (bytes < gb) return (bytes / mb).toFixed(2) + ' GB';
				if (bytes < tb) return (bytes / gb).toFixed(2) + ' TB';
			}

			function createPost(response) {
				const viewing = uri['view'];
				const template = $('#post').clone().removeClass('visually-hidden');
				const title = template.find('#title');
				title.text(response.title);
				template.find('#content').text(response.content);
				template.find('#updated').text(response.updated);
				template.find('#author').text(response.username);

				for (let i = 0; i < response.attachments.length; i++) {
					const og = template.find('#attachment');
					const attachment = response.attachments[i];
					const attach = og.clone().removeClass('visually-hidden');
					attach.attr('id', '');
					attach.find('#name').text(attachment.name + ' (' + formatMemory(attachment.size) + ')');
					attach.find('#name').click(function() {
						window.open(attachment.path);
					});
					og.after(attach);
				}

				const edit = template.find('#edit');
				const del = template.find('#delete');
				const reply = template.find('#reply');

				edit.click(function() {
					window.location.href = '<?= base_url('/posts/edit/') ?>' + response.id;
				});
				reply.click(function() {
					window.location.href = '<?= base_url('/posts/create/parent/') ?>' + response.id + '/category/' + response.category;
				});
				del.click(function() {
					const post = response;
					$.ajax({
						url: '<?= base_url('/posts/post/') ?>' + post.id,
						type: 'DELETE',
						headers: {
							'X-Token': localStorage.getItem('token')
						},
						success: function(response) {
							response = $.parseJSON(response);
							if (response.status) {
								if (post.parent === null) {
									window.location.href = '<?= base_url('/posts/') ?>';
								} else {
									if (viewing === post.id) {
										window.location.href = '<?= base_url('/posts/view/') ?>' + post.parent;
									} else {
										window.location.href = '<?= base_url('/posts/view/') ?>' + viewing;
									}
								}
							} else {
								alert(response.message);
							}
						},
						error: function(response) {
							if (response) {
								response = $.parseJSON(response.responseText);
								if (response.message)
									alert(response.message);
								else
									alert('Error deleting post');
							} else {
								alert('Error deleting post');
							}
						}
					});
				});

				if (!response.action) {
					edit.remove();
					del.remove();
				}

				if (!response.action && !response.reply) {
					reply.remove();
				}

				template.attr('id', 'post-' + response.id);
				title.click(function() {
					window.location.href = '<?= base_url('/posts/view/') ?>' + response.id;
				});
				title.css('cursor', 'pointer');
				return template;
			}

			function processPost(response) {
				const queue = [response];
				while (queue.length > 0) {
					const current = queue.shift();
					const post = createPost(current);
					if (current.parent === null) {
						$('#posts').append(post);
						if ($('.op').length === 0) {
							post.addClass('op');
						}
					} else {
						if ($('.op').length === 0) {
							$('#posts').append(post);
							post.addClass('op');
							const thread = post.find('#parentThread');
							thread.removeClass('visually-hidden');
							thread.css('cursor', 'pointer');
							thread.click(function() {
								window.location.href = '<?= base_url('/posts/view/') ?>' + current.parent;
							});
						} else {
							const holder = $('#post-' + current.parent);
							holder.append(post);
							post.css('width', '90%');
							post.css('margin-top', '1rem');
							post.css('float', 'right');
						}
					}
					for (let i = 0; i < current.replies.length; i++) {
						const data = current.replies[i];
						queue.push(data);
					}
				}
			}

			$(document).ready(async function() {
				const postId = uri['view'];

				if (!postId) {
					window.location.href = '<?= base_url('/posts') ?>';
					return;
				}

				await $.ajax({
					url: '<?= base_url('/posts/post/') ?>' + postId,
					type: 'GET',
					headers: {
						'X-Token': localStorage.getItem('token')
					},
					success: async function (response) {
						response = $.parseJSON(response);
						document.title = response.title;
						await processPost(response);
					},
					error: function(response) {
						alert('Error fetching post')
					}
				});
			})

		</script>
	</body>
</html>
