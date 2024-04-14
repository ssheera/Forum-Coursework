<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Home']); ?>
	<body class="mb-5">
		<?php $this->load->view('nav', ['page' => 'home']); ?>
		<script>
			if (localStorage.getItem('token')) {
				document.write(`
				<div class="container d-flex flex-row gap-4">
					<a href="/posts/author/self" style="text-decoration: underline; text-underline-offset: 3px" class="nav-link text-dark mt-3">Your Posts</a>
					<a href="/posts/create" style="text-decoration: underline; text-underline-offset: 3px" class="nav-link text-dark mt-3">Create Post</a>
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
		<div id="posts-block" class="post-block container visually-hidden" style="margin-top: 3rem; width: 60rem">
			<div class="container rounded-2 rounded-bottom-0" style="background: #F0F0F0">
				<h6 id="category" class="text-dark text-capitalize fw-semibold py-2 m-0"></h6>
			</div>
			<div id="posts" class="container overflow-auto bg-secondary bg-opacity-25 rounded-2 rounded-top-0 h-auto px-0" style="max-height: 300px;">
				<div id="post" class="border-0 h-auto p-2 bg-gradient visually-hidden" style="cursor: pointer">
					<p id="title" class="text-dark fw-bold m-0 ps-3"></p>
					<p id="author" class="text-dark fst-italic m-0 ps-3" style="font-size: 0.9rem"></p>
					<div class="d-flex flex-row justify-content-between">
						<p id="replies" class="text-dark m-0 ps-3" style="font-size: 0.9rem"></p>
						<p id="updated" class="text-dark m-0 pe-3" style="font-size: 0.9rem">2024-01-01</p>
					</div>
				</div>
			</div>
		</div>
		<script>
			$(document).ready(function() {
				$.ajax({
					url: '/posts/categories',
					type: 'GET',
					success: async function (data) {
						data = $.parseJSON(data);
						for (let category of data) {

							let block = $('#posts-block')
								.clone()
								.removeClass('visually-hidden')
								.attr('id', 'posts-block-' + category.id);
							block.find('#category').text(category.name);

							await $.ajax({
								url: '/posts/category/' + category.id,
								type: 'GET',
								headers: {
									'X-Token': localStorage.getItem('token'),
									<?php if (isset($author)) echo "'F-Author': '$author',"; ?>
									<?php if (isset($category)) echo "'F-Category': '$category',"; ?>
								},
								success: function (data) {
									data = $.parseJSON(data);
									if (data.length === 0) return;
									block.appendTo('body');
									let post_counter = 0;
									data.forEach(function (post) {
										<?php if (!isset($author) && !isset($category)) { ?>
											if (post.parent !== null) return;
										<?php } ?>
										let postBlock = block.find('#post')
											.clone()
											.appendTo(block.find('#posts'))
											.removeClass('visually-hidden')
											.attr('id', 'post-' + post.id);
										postBlock.find('#title').text(post.title);
										postBlock.find('#author').text('by ' + post.author);
										postBlock.find('#replies').text(post.replies + ' replies');
										postBlock.find('#updated').text(post.updated);
										postBlock.click(function () {
											window.location.href = '/posts/view/' + post.id;
										})
										if (post_counter++ % 2 === 0) {
											postBlock.css('background', '#FFFFFF');
										} else {
											postBlock.css('background', 'rgba(234, 232, 233)');
										}
									})
								}
							})
						}
					}
				});
			})
		</script>
	</body>
</html>
