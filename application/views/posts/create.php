<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Create Post']); ?>
	<body>
		<?php $this->load->view('nav'); ?>
		<div id="root"></div>
	</body>

	<script type="text/babel">
		
		class Create extends React.Component {

			constructor() {
				super();
				// state contains categories and attachments
				// attachments are held after uploading and before submitting
				this.state = {
					categories: [],
					attachments: []
				}
			}

			async componentDidMount() {

				// get local storage and token
				const localStorage = window.localStorage;
				const token = localStorage.getItem('token');

				// if token not set, redirect to login
				if (!token) {
					window.location.href = '<?= base_url('/auth/login') ?>';
					return;
				}

				// if the post is a reply, i.e. a parent post is set in the url, we don't need
				// to grab the categories, the server will autofill it
				if (uri['parent'])
					return;

				// variable to hold categories
				const categories = [];

				await $.ajax({
					url: '<?= base_url('/posts/categories') ?>',
					type: 'GET',
					headers: {
						// adding token to headers, so we can get only
						// the categories that the user has access to
						'X-Token': token
					},
					success: function(data) {
						data = $.parseJSON(data);
						data.forEach(function(category) {
							categories.push(category);
						});
					},
					error: function(response) {
						if (response.status === 401) {
							// 401 is unauthorized, so we remove the invalid token and redirect to login
							localStorage.removeItem('token');
							window.location.href = '<?= base_url('/auth/login') ?>';
						}
					}
				});

				// populate the categories in the state
				this.setState({ categories: categories });
			}
			
			render() {

				// the parent post id, if set, the post is a reply
				const post_parent = uri['parent'];
				const reply = !!post_parent;

				// function to format memory size
				function formatMemory(bytes) {
					// array of units
					const units = ['B', 'KB', 'MB', 'GB', 'TB'];
					// variable to hold the index of the unit
					let unitIndex = 0;
					// loop through the units and divide the bytes by 1024,
					// dividing by 1024 will increase the unit index
					while (bytes >= 1024 && unitIndex < units.length - 1) {
						bytes /= 1024;
						unitIndex++;
					}
					// if the unit index is 0, return the bytes and the unit
					if (unitIndex === 0) return bytes + ' ' + units[unitIndex];
					// return the formatted memory size, 2 decimal places with the highest unit
					return bytes.toFixed(2) + ' ' + units[unitIndex];
				}

				// function to handle input, this will resize the textarea with
				// the attachments container
				function handleInput(event) {
					// the target is textarea
					const target = $(event.target);
					const ele = target[0];
					// set height css to auto
					target.css('height', 'auto');
					// height to scroll height - 16, 16 is what I found to be the best
					target.height(ele.scrollHeight - 16);
					// set the max height of the attachments container based on the scroll height
					$('#attachments').css('max-height', `${ele.scrollHeight + 258}px`);
				}

				// function to handle upload, this will add the attachment to the state
				function handleUpload(event) {
					// check if the attachments are more than 5
					if (this.state.attachments.length >= 5) {
						alert('Maximum of 5 attachments allowed');
						return;
					}
					// get the target, which is the file input
					const target = $(event.target);
					let file = target.prop('files')[0];

					// reset the file input, allowing duplicate uploads
					target.val('');

					// file size check, 8 MB is the maximum size
					if (file.size > 1024 * 1024 * 8) {
						alert('File size exceeds 8 MB');
						return;
					}

					// Create the attachment object
					const attachment = {
						name: file.name,
						size: file.size,
						file: file
					};

					// Update the state with the attachment
					this.setState({ attachments: [...this.state.attachments, attachment] });
				}

				// function to remove an attachment
				function removeAttachment(attachment) {
					// find the index of the attachment
					const index = this.state.attachments.indexOf(attachment);
					// if the index is found, remove the attachment
					if (index > -1)
						this.state.attachments.splice(index, 1);
					// update the state
					this.setState({ attachments: this.state.attachments });
				}

				// function to handle submit, this will send the post data to the server
				// and upload attachments
				async function handleSubmit() {
					// Get the token, category, keywords, title, content and attachments
					const token = window.localStorage.getItem('token');
					const category = $('#category').val();
					const keywords = $('#keywords').val();
					const title = $('#title').val();
					const content = $('#content').val();
					const attachments = this.state.attachments;

					// Get spinner components
					const idleSubmit = $('#idle-submit');
					const runningSubmit = $('#running-submit');

					// Add the spinner to the submit button
					idleSubmit.addClass('visually-hidden');
					runningSubmit.removeClass('visually-hidden');

					// Construct form data
					let data = {
						keywords: keywords,
						title: title,
						content: content
					};

					// Calculate whether to send the parent or category
					// since it is calculated if the post is a reply
					if (uri['parent']) {
						data.parent = uri['parent'];
					} else {
						data.category = category;
					}

					// send the post data to the server
					await $.ajax({
						url: '<?= base_url('/posts/create') ?>',
						type: 'POST',
						headers: {
							'X-Token': token
						},
						data: data,
						success: async function(response) {
							response = $.parseJSON(response);
							if (response.status) {
								// post has been successfully created, get the post id
								const postId = response.id;
								// loop through the attachments and upload them
								// create formData to upload files efficiently
								const formData = new FormData();
								for (const attachment of attachments)
									formData.append("files[]", attachment.file);

								// send POST to attach/<id> to upload the attachments
								await $.ajax({
									url: '<?= base_url('/posts/attach/') ?>' + postId,
									type: 'POST',
									processData: false,
									contentType: false,
									headers: {
										'X-Token': token
									},
									data: formData,
								});

								// redirect to the post view
								if (post_parent) {
									// if the post is a reply, redirect to the parent post
									window.location.href = '<?= base_url('/posts/view/') ?>' + post_parent;
								} else {
									// if the post is not a reply, redirect to the post
									window.location.href = '<?= base_url('/posts/view/') ?>' + postId;
								}
							} else {
								alert(response.message);
							}
						},
						error: function(response) {
							if (response.status === 401) {
								localStorage.removeItem('token');
								window.location.href = '<?= base_url('/auth/login') ?>';
							} else {
								alert('An error occurred');
							}
						}
					});

					// Everything uploaded and sent, remove the spinner
					idleSubmit.removeClass('visually-hidden');
					runningSubmit.addClass('visually-hidden');
				}

				return (
					<div className="container mt-5 mb-5">
						<div role="form" className="card mt-5 mx-5 rounded-4 shadow border-0">
							<input type="file" id="upload" className="visually-hidden" onInput={handleUpload.bind(this)}/>
							<div className="card-header bg-white border-bottom-0 rounded-4">
								<h4 className="card-title text-secondary mx-2 mt-3 fw-bold">Create Post</h4>
							</div>
							<div className="card-body mx-2">
								<div className="d-flex flex-row">
									<div className="col-9">
										<div className="me-4">
											<div>
												<div className="d-flex flex-row">
													<div className="col-6">
														<div className="form-floating mb-4 me-2">
															<select id="category" className="form-select" aria-label="Category" disabled={reply} required={!reply}>
																{this.state.categories.map(category => (
																	<option value={category.id}>{category.name}</option>
																))}
															</select>
															<label htmlFor="category" className="form-label text-secondary">Category</label>
														</div>
													</div>
													<div className="col-6">
														<div className="mb-4 ms-2"></div>
													</div>
												</div>
												<div className="d-flex flex-row">
													<div className="col-6">
														<div className="form-floating me-2 mb-4">
															<input id="keywords" className="form-control" aria-label="Keywords" placeholder="Keywords"/>
															<label htmlFor="keywords" className="form-label text-secondary">Keywords</label>
														</div>
													</div>
												</div>
											</div>
											<div className="form-floating col-12 mb-4">
												<input id="title" className="form-control" aria-label="Title" placeholder="Title" />
												<label htmlFor="title" className="form-label text-secondary">Title</label>
											</div>
											<div className="form-floating col-12 mb-4">
                                        		<textarea id="content" className="form-control" aria-label="Content" placeholder="Content"
														  style={{resize: "none", minHeight: "98px"}} onInput={handleInput}/>
												<label htmlFor="content" className="form-label text-secondary">Content</label>
											</div>
										</div>
									</div>
									<div className="col-3" style={{display: this.state.attachments.length > 0 ? "block" : "none"}}>
										<div className="ms-3">
											<p className="text-secondary fw-bold p-3 pb-0">Attachments</p>
											<div id="attachments" className="d-flex flex-column overflow-auto p-3 pt-1 gap-3" style={{ minHeight: 296, maxHeight: 296 }}>
												{this.state.attachments.map(attachment => (
													<div className="col-12 rounded-3" style={{ minHeight: 70, backgroundColor: "#f9f9f9" }}>
														<div className="d-flex flex-row">
															<div className="col-9">
																<div className="m-2">
																	<p className="text-start text-dark text-truncate mb-0">{attachment.name}</p>
																	<p className="text-start text-secondary mb-0">{formatMemory(attachment.size)}</p>
																</div>
															</div>
															<div className="col-3">
																<div className="float-end">
																	<button className="btn border-0" onClick={() => removeAttachment.bind(this)(attachment)}>
																		<i className="fa-solid fa-xmark fa-2xs bg-transparent"></i>
																	</button>
																</div>
															</div>
														</div>
													</div>
												))}
											</div>
										</div>
									</div>
								</div>
								<button className="btn btn-theme me-2" onClick={() => $('#upload').click()}>Upload Attachment</button>
								<button className="btn btn-theme" onClick={handleSubmit.bind(this)}>
									<span id="idle-submit">Create</span>
									<span id="running-submit" className="visually-hidden spinner-border spinner-border-sm text-white" role="status"></span>
								</button>
							</div>
						</div>
					</div>
				)
			}
		}

		ReactDOM.render(<Create />,  $('#root')[0]);
		
	</script>

</html>
		
		
