<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Viewing Post']); ?>
	<body>
		<?php $this->load->view('nav'); ?>

		<div id="root"></div>

		<script type="text/babel">

			class View extends React.Component {

				constructor() {
					super();
					// Set the default state, only updating and holding
					// the posts on the page
					this.state = {
						posts: [],
					};
				}

				async componentDidMount() {
					// Variable to hold the posts
					// this will be used to update the state at the end
					let posts = [];

					// Get the post id from the uri variable
					const postId = uri['view'];

					// if it not set, redirect to the posts page
					if (!postId) {
						window.location.href = '<?= base_url('/posts') ?>';
						return;
					}

					// function to process the post data
					function processPost(post) {

						// once_flag to be used for the original post
						let first = true;

						// function to create structure for the post that is used later on
						const createPost = (data) => {
							return {
								id: data.id,
								title: data.title,
								category: data.category,
								content: data.content,
								author: data.username,
								updated: data.updated,
								attachments: data.attachments,
								parent: data.parent,
								parent_content: '',
								action: data.action,
								reply: data.reply,
								og: false
							};
						};

						// queue to hold the posts,
						const queue = [post];
						// array to hold the new posts
						const newPosts = [];

						// while the queue is not empty, FIFO queue
						while (queue.length > 0) {
							// the first element in the queue
							const current = queue.shift();
							// get structure for the post, used for the newPosts array
							const post = createPost(current);

							// using the once_flag
							if (first) {
								// set the post to og (original post)
								// set the once_flag to false
								post.og = true;
								first = false;
							}

							// find the parent post of the current post,
							// technically, it should be in the newPosts array since it is done hierarchically
							const parent = newPosts.find(p => p.id === current.parent);
							// if it is found, set the parent_content field
							// parent_content is used for quoting the parent post
							if (parent)
								post.parent_content = parent.content
							// push the post to the newPosts array
							newPosts.push(post);
							// push all the child posts to the queue to process
							current.replies.forEach(reply => queue.push(reply));
						}

						// return the newPosts array
						return newPosts;
					}

					// send GET request to the server to get information about the post
					await $.ajax({
						url: '<?= base_url('/posts/post/') ?>' + postId,
						type: 'GET',
						headers: {
							// send the token in the header to get information
							// about replying/editing/deleting
							'X-Token': localStorage.getItem('token')
						},
						success: async function (response) {
							response = $.parseJSON(response);
							// get the post data back
							// set page title to the post title
							document.title = "Viewing - " + response.title;
							// process the post data and set it to the posts variable
							posts = processPost(response);
						},
						error: function(response) {
							alert('Error fetching post')
						}
					});

					// update the state with the posts
					this.setState({ posts: posts });
				}

				render() {

					// the token used for authentication
					const token = localStorage.getItem('token');

					// function to handle edit button
					function handleEdit(post) {
						// send the user to the edit page using the post id
						window.location.href = '<?= base_url('/posts/edit/') ?>' + post.id;
					}

					// function to handle delete button
					function handleDelete(post) {

						// function to delete the post from the page
						// it is done like this since I need reference to 'this'/the class
						// updates the state with the new posts now one has been deleted
						const deletePost = () => {
							let posts = this.state.posts;
							posts = posts.filter(p => p.id !== post.id);
							this.setState({ posts: posts });
						}

						// send DELETE request to the server to delete the post
						$.ajax({
							url: '<?= base_url('/posts/post/') ?>' + post.id,
							type: 'DELETE',
							headers: {
								'X-Token': token,
							},
							success: function(response) {
								response = $.parseJSON(response);
								if (response.status) {
									// now it has been deleted
									// if there is no parent, we can go to main page
									if (post.parent === null) {
										window.location.href = '<?= base_url('/posts/') ?>';
									} else {
										// there is parent, if the viewing post is the post deleted, we go to parent
										// otherwise, we just update state
										if (uri['view'] === post.id) {
											window.location.href = '<?= base_url('/posts/view/') ?>' + post.parent;
										} else {
											deletePost();
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
					}

					// function to handle reply button
					function handleReply(post) {
						// navigate to a create page with the parent id in the url
						window.location.href = '<?= base_url('/posts/create/parent/') ?>' + post.id;
					}

					// function to handle navigating to the parent post
					function handleParentThread(post) {
						// navigate to the parent post
						window.location.href = '<?= base_url('/posts/view/') ?>' + post.parent;
					}

					// function to handle scrolling to the parent post
					function handleParentScroll(post) {
						// each post has id of 'post-<id>', so we can scroll to the parent post
						// using html, not sure how to do this with jQuery
						const element = document.getElementById('post-' + post.parent);
						element.scrollIntoView({ behavior: "smooth" });
					}

					// function to handle viewing the post
					function handleView(post) {
						// href to the post, /view/<id>
						window.location.href = '<?= base_url('/posts/view/') ?>' + post.id;
					}

					return (
						<div className="d-flex flex-column">
							<div className="container mt-4">
								<div className="d-flex flex-row float-end border-0">
									<div style={{ width: "30%" }}></div>
									<label htmlFor="searchBox" className="form-label visually-hidden">Search</label>
									<input id="searchBox"
										   className="form-control border-0 bg-secondary bg-opacity-25 ps-3 pe-1 py-1 rounded-3 rounded-end-0"
										   placeholder="Search"
										   aria-label="Search" />
									<button id="searchButton"
											className="px-2 py-0 m-0 btn btn-light border-0 rounded-3 rounded-start-0">
										<i className="fas fa-search"></i>
									</button>
								</div>
							</div>
							<div className="container mt-3 mb-3">
								<div className="container m-2 mx-auto" style={{ width: "70rem" }}>

									{ this.state.posts.map(post => {
										// loop through each post stored in state
										// doing this ensures the view is updated when there is change
										return (
											<div id={"post-" + post.id} className={this.state.posts.indexOf(post) > 0 ? "mt-3" : ""}>
												<div className="shadow rounded-2">
													<div className="container d-flex rounded-top justify-content-between" style={{ background: "#F0F0F0" }}>
														<h6 onClick={() => handleView(post)} className="text-dark text-capitalize py-2 m-0" style={{cursor: "pointer"}}>
															{post.title}
														</h6>
														{ (post.og && post.parent) &&
															<p onClick={() => handleParentThread(post)} className="text-dark text-capitalize fst-italic float-end py-2 m-0" style={{ cursor: "pointer", fontSize: "0.8rem" }}>
																Parent Thread
															</p>
														}
													</div>
													<div className="container">
														{ post.parent_content.length > 0 &&
															// if there is parent content, show it as quoted
															<div className="text-dark p-3 m-0 pb-0" style={{width: "fit-content"}}>
																<p onClick={() => handleParentScroll(post)} className="m-0 p-1 px-2 border-1 rounded-2 rounded-bottom-0"
																   style={{
																	   fontSize: "0.8rem",
																	   background: "#f2f2f2",
																	   cursor: "pointer"
																   }}>
																	Quote
																</p>
																<p className="text-dark fst-italic border-1 rounded-2 rounded-top-0 p-2 mb-0"
																   style={{
																	   fontSize: "0.7rem",
																	   whiteSpace: "pre-line",
																	   background: "#e2e2e2",
																   }}>
																	{post.parent_content}
																</p>
															</div>
														}
														<p className="text-dark p-3 m-0" style={{ whiteSpace: "pre-line", fontSize: "0.9rem" }}>{post.content}</p>
													</div>
													{ post.attachments.map(attachment => {
														// loop through each attachment
														return (
															<div className="container d-flex">
																<p onClick={() => window.open(attachment.path)} className="m-0 p-3 pt-0 text-primary"
																   style={{ cursor: "pointer",  fontSize: "0.9rem" }}>
																	{attachment.name}
																</p>
															</div>
														)
													}) }
												</div>
												<div className="mx-2 d-flex justify-content-between">
													<div className="d-flex gap-3">
														{ post.action && (
															// var for whether the user can edit/delete
															<>
																<p onClick={() => handleEdit(post)} className="nav-link text-dark mt-2" style={{ cursor: "pointer", fontSize: "0.9rem" }}>Edit</p>
																<p onClick={() => handleDelete.bind(this)(post)} className="nav-link text-dark mt-2" style={{ cursor: "pointer", fontSize: "0.9rem" }}>Delete</p>
															</>
														)}
														{ post.reply && (
															// var for whether the user can reply
															<p onClick={() => handleReply(post)} className="nav-link text-dark mt-2" style={{ cursor: "pointer", fontSize: "0.9rem" }}>Reply</p>
														)}
													</div>
													<div className="d-flex flex-column text-end">
														<p className="text-dark m-0 mt-2" style={{ fontSize: "0.9rem" }}>{post.updated}</p>
														<p className="text-dark m-0" style={{ fontSize: "0.9rem" }}>{post.author}</p>
													</div>
												</div>
											</div>
										)
									}) }
								</div>
							</div>
						</div>
					);
				}
			}

			ReactDOM.render(<View />,  $('#root')[0]);

		</script>

	</body>
</html>
