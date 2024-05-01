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
					this.state = {
						data: [],
						search: ''
					}
				}

				async componentDidMount() {
					const categories = []
					await $.ajax({
						url: '<?= base_url('/posts/categories') ?>',
						type: 'GET',
						success: function (data) {
							data = $.parseJSON(data);
							for (let category of data) {
								categories.push(category);
							}
						}
					})

					let headers = {
						'X-Token': localStorage.getItem('token'),
					};

					const author = uri['author'];
					const category = uri['category'];
					const term = uri['term'];

					if (author) headers['X-Filter-Author'] = author;
					if (category) headers['X-Filter-Category'] = category;
					if (term) headers['X-Filter-Term'] = term;

					const data = []

					for (let category of categories) {
						const posts = []
						await $.ajax({
							url: '<?= base_url('/posts/category/') ?>' + category.id,
							type: 'GET',
							headers: headers,
							success: function (data) {
								data = $.parseJSON(data);
								for (let post of data) {
									if (post.parent === null)
										posts.push(post);
								}
							}
						})

						if (posts.length === 0) continue;

						data.push({
							category: category,
							posts: posts
						});
					}

					this.setState({data: data});

				}

				render() {

					const loggedIn = localStorage.getItem('token') !== null;

					function handleSearch() {
						const search = $('#searchBox').val();
						if (search === '') return;
						// TODO: Implement search
					}

					return (
						<div className="d-flex flex-column" style={{marginBottom: '3rem'}}>
							{ loggedIn &&
								<div className="container d-flex flex-row gap-4">
									<a href="<?= base_url('/posts/author/self') ?>" style={{textDecoration: 'underline', textUnderlineOffset: '3px'}} className="nav-link text-dark mt-3">Your Posts</a>
									<a href="<?= base_url('/posts/create') ?>" style={{textDecoration: 'underline', textUnderlineOffset: '3px'}} className="nav-link text-dark mt-3">Create Post</a>
								</div>
							}
							<div className="container mt-4">
								<div className="d-flex flex-row float-end border-0">
									<div style={{width: '30%'}}></div>
									<label for="searchBox" className="form-label visually-hidden">Search</label>
									<input id="searchBox" className="form-control border-0 bg-secondary bg-opacity-25 ps-3 pe-1 py-1 rounded-3 rounded-end-0" placeholder="Search" />
									<button id="searchButton" className="px-2 py-0 m-0 btn btn-light border-0 rounded-3 rounded-start-0" onClick={handleSearch}>
										<i className="fas fa-search"></i></button>
								</div>
							</div>
							{ this.state.data.map((data, index) => (
								<div key={"cat_" + index} className="container" style={{marginTop: '3rem', width: '60rem'}}>
									<div className="container rounded-2 rounded-bottom-0" style={{background: '#F0F0F0'}}>
										<h6 className="text-dark text-capitalize fw-semibold py-2 m-0">{data.category.name}</h6>
									</div>
									<div className="container overflow-auto bg-secondary bg-opacity-25 rounded-2 rounded-top-0 h-auto px-0" style={{maxHeight: '300px'}}>
										{ data.posts.map((post, index) => (
											<div key={"post_" + index} className="border-0 h-auto p-2 bg-gradient"
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
