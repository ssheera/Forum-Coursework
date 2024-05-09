<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Advanced Search']); ?>
	<body>
		<?php $this->load->view('nav'); ?>

		<div id="root"></div>

		<script type="text/babel">

			class Search extends React.Component {

				constructor() {
					super();
					// state holds the categories
					this.state = {
						categories: []
					}
				}

				async componentDidMount() {

					// variable to hold the categories, later gets used to populate the state
					const categories = [];

					await $.ajax({
						url: '<?= base_url('/posts/categories') ?>',
						type: 'GET',
						success: function(data) {
							data = $.parseJSON(data);
							// server-side this is sent as raw array so json is also an array
							data.forEach(function(category) {
								// push each category to the array
								categories.push(category);
							});
						}
					});

					// set the state with the categories
					this.setState({ categories: categories });
				}

				render() {

					// function to handle the search button click
					function handleSearch() {
						// get the values of the inputs, author, keywords and category
						// these only the values needed for searching
						const author = $('#author').val();
						const keywords = $('#keywords').val();
						const category = $('#category').val();

						// the main page where the search results will be displayed
						// manually add each filter, can't add empty filters so this is the best way
						let location = '<?= base_url('/posts') ?>';
						if (author)
							location += '/author/' + author;
						if (keywords)
							location += '/term/' + keywords;
						if (category)
							location += '/category/' + category;

						window.location.href = location
					}

					return (
						<div role="form" className="d-flex flex-column container mt-5 mb-5">
							<div className="row">
								<h4 className="text-secondary my-3 fw-bold">Advanced Search</h4>
							</div>
							<div className="form-floating me-2 mb-4 col-4">
								<input id="author" className="form-control" aria-label="Author" placeholder="Author"/>
								<label htmlFor="author" className="form-label text-secondary">Author</label>
							</div>
							<div className="form-floating me-2 mb-4 col-4">
								<input id="keywords" className="form-control" aria-label="Keywords" placeholder="Keywords"/>
								<label htmlFor="keywords" className="form-label text-secondary">Keywords</label>
							</div>
							<div className="form-floating mb-4 me-2 col-4">
								<select id="category" className="form-select" aria-label="Category">
									<option value="">Select a category</option>
									{this.state.categories.map(category => (
										<option value={category.id}>{category.name}</option>
									))}
								</select>
								<label htmlFor="category" className="form-label text-secondary">Category</label>
							</div>
							<div className="mb-4">
								<button onClick={handleSearch} className="btn btn-theme col-4">Search</button>
							</div>
						</div>
					);
				}
			}

			ReactDOM.render(<Search />,  $('#root')[0]);

		</script>

	</body>
</html>
