<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Home']); ?>
	<body>
		<?php $this->load->view('nav'); ?>

		<div id="root"></div>

		<script type="text/babel">

			class Posts extends React.Component {

				constructor() {
					super();
					// state consists of posts (data), categories,
					// search term and username
					// used for "Your Posts" filter
					this.state = {
						data: [],
						categories: [],
						search: '',
						username: ''
					}
				}

				async componentDidMount() {
					// grab the uri filters, if any
					const author = uri['author'];
					const category = uri['category'];
					const term = uri['term'];
					// variable for username
					let username = '';
					// send request and get the username of the user
					await $.ajax({
						url: '<?= base_url('/auth/self') ?>',
						type: 'GET',
						headers: {
							'X-Token': localStorage.getItem('token')
						},
						success: function (data) {
							data = $.parseJSON(data);
							username = data.username;
						}
					})
					// get the categories, sending GET to /posts/categories
					const categories = []
					await $.ajax({
						url: '<?= base_url('/posts/categories') ?>',
						type: 'GET',
						success: function (data) {
							data = $.parseJSON(data);
							// parse as json and loop the array
							// push each category to the categories array
							for (let category of data)
								categories.push(category);
						}
					})
					// update the state
					this.setState({categories: categories, username: username});
					// call update posts function to apply the filters and
					// find posts for the page
					await this.updatePosts(author, category, term);
				}

				async updatePosts(author, category, term) {
					// mutable headers object
					let headers = {};

					// apply filters to headers, if they are present
					if (author) headers['X-Filter-Author'] = author;
					if (category) headers['X-Filter-Category'] = category;
					if (term) headers['X-Filter-Term'] = term;

					// if any filter is applied, ignore checking for original posts
					// it should show every and any post
					const ignoreParent = author || category || term;

					// hold variable for holding category and posts
					const data = []
					// loop through the categories
					for (let category of this.state.categories) {
						// hold variable for posts per category
						const posts = []
						// send request to /posts/posts/<id>
						await $.ajax({
							url: '<?= base_url('/posts/posts/') ?>' + category.id,
							type: 'GET',
							headers: headers,
							success: function (data) {
								data = $.parseJSON(data);
								// loop through the posts and push them to the posts array
								for (let post of data) {
									// if ignoreParent is true, or it has no parent/is original, push
									if (ignoreParent || post.parent === null)
										posts.push(post);
								}
							}
						})
						// if there are no posts, continue, no need to have empty categories
						if (posts.length === 0) continue;
						// push the category and posts to the data array
						data.push({
							category: category,
							posts: posts
						});
					}
					// update the state
					this.setState({data: data});
				}

				render() {

					// variable for the user token, mainly used for conditional rendering
					const token = localStorage.getItem('token') !== null;

					return (
						<div className="d-flex flex-column" style={{marginBottom: '3rem'}}>
							<div className="container d-flex flex-row gap-4">
								{token && (
									<>
										<p onClick={() => this.updatePosts(this.state.username, undefined, undefined)}
										   style={{ textDecoration: "underline", textUnderlineOffset: "3px", cursor: "pointer"}}
										   className="nav-link text-dark mt-3">Your Posts
										</p>
										<a href="<?= base_url('/posts/create') ?>"
										   style={{ textDecoration: "underline", textUnderlineOffset: "3px" }}
										   className="nav-link text-dark mt-3">Create Post
										</a>
									</>
								)}
								<a href="<?= base_url('/posts/search') ?>"
								   style={{ textDecoration: "underline", textUnderlineOffset: "3px" }}
								   className="nav-link text-dark mt-3">Advanced Search
								</a>
							</div>
							<div className="container mt-4">
								<div className="d-flex flex-row float-end border-0">
									<div style={{width: '30%'}}></div>
									<label htmlFor="searchBox" className="form-label visually-hidden">Search</label>
									<input id="searchBox" className="form-control border-0 bg-secondary bg-opacity-25 ps-3 pe-1 py-1 rounded-3 rounded-end-0" placeholder="Search" aria-label="Search"/>
									<button id="searchButton" className="px-2 py-0 m-0 btn btn-light border-0 rounded-3 rounded-start-0"
											onClick={() => this.updatePosts(undefined, undefined, $('#searchBox').val())}>
										<i className="fas fa-search"></i></button>
								</div>
							</div>
							{ this.state.data.map((data, index) => (
								<div key={"cat-" + index} className="container" style={{marginTop: '3rem', width: '60rem'}}>
									<div className="container rounded-2 rounded-bottom-0" style={{background: '#F0F0F0'}}>
										<h6 className="text-dark text-capitalize fw-semibold py-2 m-0">{data.category.name}</h6>
									</div>
									<div className="container overflow-auto bg-secondary bg-opacity-25 rounded-2 rounded-top-0 h-auto px-0" style={{maxHeight: '300px'}}>
										{ data.posts.map((post, index) => (
											<div key={"post-" + index} className="border-0 h-auto p-2 bg-gradient"
												 style={{cursor: 'pointer', background: index % 2 === 0 ? '#FFFFFF' : 'rgba(234, 232, 233)'}}
												 onClick={() => window.location.href = '<?= base_url('/posts/view/') ?>' + post.id}>
												<p className="text-dark fw-bold m-0 ps-3">{post.title}</p>
												<p className="text-dark fst-italic m-0 ps-3" style={{fontSize: '0.9rem'}}>by {post.author}</p>
												<div className="d-flex flex-row justify-content-between">
													<p className="text-dark m-0 ps-3" style={{fontSize: '0.9rem'}}>{post.replies} replies</p>
													<p className="text-dark m-0 pe-3" style={{fontSize: '0.9rem'}}>{post.updated}</p>
												</div>
											</div>
										))}
									</div>
								</div>
							))}
						</div>
					)
				}

			}

			ReactDOM.render(<Posts />, $('#root')[0]);

		</script>
	</body>
</html>
