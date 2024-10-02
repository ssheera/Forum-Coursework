<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Edit Post']); ?>
	<body>
		<?php $this->load->view('nav'); ?>
		<div id="root"></div>
	</body>

	<script type="text/babel">
		
		class Edit extends React.Component {

			constructor() {
				super();
				// set the state with the attachments, title, content and keywords
				// savedAttachments is a copy of the attachments to be used for comparison
				// so that we can delete attachments that are not in the state
				this.state = {
					attachments: [],
					savedAttachments: [],
					title: '',
					content: '',
					keywords: ''
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

				// check if post id is set in the uri
				if (!uri['edit']) {
					window.location.href = '<?= base_url('/posts') ?>';
					return;
				}

				// create the data object, this goes into the state
				const data = {
					title: '',
					content: '',
					keywords: '',
					attachments: []
				}

				// send GET request and request information about the post
				// such as title, content, keywords and attachments
				await $.ajax({
					url: '<?= base_url('/posts/post/') ?>' + uri['edit'],
					type: 'GET',
					headers: {
						'X-Token': localStorage.getItem('token')
					},
					success: async function (response) {
						response = $.parseJSON(response);
						// set the document title to the post title
						document.title = "Editing - " + response.title;
						// get the title, content and keywords from the response
						data.title = response.title;
						data.content = response.content;
						data.keywords = response.keywords;
						// interpret the attachments from the response
						for (const attachment of response.attachments) {
							const file = {
								// These attachments have an id to specify that they already exist on server
								id: attachment.id,
								name: attachment.name,
								size: attachment.size,
								file: null
							}
							data.attachments.push(file);
						}
					},
					error: function(response) {
						alert('Error fetching post')
					}
				});

				// set the state with the data
				// savedAttachments is a copy of the attachments to be used for comparison
				this.setState({ title: data.title, content: data.content, keywords: data.keywords,
					attachments: data.attachments,
					savedAttachments: data.attachments.map(attachment => attachment)});

				// clone of handleInput
				// get the textarea element and resize the textarea with the attachments container
				const target = $('textarea');
				const ele = target[0];
				target.css('height', 'auto');
				target.height(ele.scrollHeight - 16);
				$('#attachments').css('max-height', `${ele.scrollHeight + 258}px`);

			}
			
			render() {

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

				// function to handle saving the post
				async function handleSubmit() {
					// Get the token, category, keywords, title, content and attachments
					const token = window.localStorage.getItem('token');
					const keywords = $('#keywords').val();
					const title = $('#title').val();
					const content = $('#content').val();
					const attachments = this.state.attachments;
					const savedAttachments = this.state.savedAttachments;

					// Get the spinner components and buttons
					const idleSubmit = $('#idle-submit');
					const runningSubmit = $('#running-submit');

					// Add the spinner to the submit button
					idleSubmit.addClass('visually-hidden');
					runningSubmit.removeClass('visually-hidden');

					// Construct form data
					let data = {
						keywords: keywords,
						title: title,
						content: content,
					};

					// send POST data to the server at /edit/<id>
					// outcome should update the post
					await $.ajax({
						url: '<?= base_url('/posts/edit/') ?>' + uri['edit'],
						type: 'POST',
						headers: {
							'X-Token': token
						},
						data: data,
						success: async function(response) {
							response = $.parseJSON(response);
							// the edit process was successful
							if (response.status) {
								// check if there were any attachments originally with the post
								if (savedAttachments.length > 0) {
									// if there were, check if any attachments were removed
									const toDelete = savedAttachments.filter(savedAttachment => {
										return !attachments.some(attachment => attachment.id === savedAttachment.id);
									});
									// of those attachments, delete them
									// send DELETE to /attach/<attachment id>
									// server automatically locates the post and deletes the attachment
									for (const attachment of toDelete) {
										await $.ajax({
											url: '<?= base_url('/posts/attach/') ?>' + attachment.id,
											type: 'DELETE',
											headers: {
												'X-Token': token
											},
											data: {
												id: attachment.id
											}
										});
									}
								}
								// loop through the attachments and upload them
								// create formData to upload files efficiently
								const formData = new FormData();
								for (const attachment of attachments)
									formData.append("files[]", attachment.file);

								// send POST to attach/<id> to upload the attachments
								await $.ajax({
									url: '<?= base_url('/posts/attach/') ?>' + uri['edit'],
									type: 'POST',
									processData: false,
									contentType: false,
									headers: {
										'X-Token': token
									},
									data: formData,
								});
								// go back to the post view
								window.location.href = '<?= base_url('/posts/view/') ?>' + uri['edit'];
							} else {
								// alert the error message
								alert(response.message);
							}
						},
						error: function(response) {
							// in case of 401, invalid token, redirect to login
							if (response.status === 401) {
								localStorage.removeItem('token');
								window.location.href = '<?= base_url('/auth/login') ?>';
							} else {
								// else, alert an error occurred
								alert('An error occurred');
							}
						}
					});

					// Everything is done, remove spinner from submit button
					idleSubmit.removeClass('visually-hidden');
					runningSubmit.addClass('visually-hidden');
				}

				return (
					<div className="container mt-5 mb-5">
						<div role="form" className="card mt-5 mx-5 rounded-4 shadow border-0">
							<input type="file" id="upload" className="visually-hidden" onInput={handleUpload.bind(this)}/>
							<div className="card-header bg-white border-bottom-0 rounded-4">
								<h4 className="card-title text-secondary mx-2 mt-3 fw-bold">Edit Post</h4>
							</div>
							<div className="card-body mx-2">
								<div className="d-flex flex-row">
									<div className="col-9">
										<div className="me-4">
											<div>
												<div className="d-flex flex-row">
													<div className="col-6">
														<div className="form-floating mb-4 me-2">
															<select id="category" className="form-select" aria-label="Category" disabled>
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
														<div className="form-floating mb-4 me-2">
															<input id="keywords" className="form-control" aria-label="Keywords" placeholder="Keywords"
																   defaultValue={this.state.keywords} />
															<label htmlFor="keywords" className="form-label text-secondary">Keywords</label>
														</div>
													</div>
												</div>
											</div>
											<div className="form-floating col-12 mb-4">
												<input id="title" className="form-control" aria-label="Title" placeholder="Title" defaultValue={this.state.title} />
												<label htmlFor="title" className="form-label text-secondary">Title</label>
											</div>
											<div className="form-floating col-12 mb-4">
                                        		<textarea id="content" className="form-control" aria-label="Content" placeholder="Content"
														  style={{resize: "none", minHeight: "98px"}}
														  onInput={handleInput}
														  defaultValue={this.state.content}/>
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
									<span id="idle-submit">Save</span>
									<span id="running-submit" className="visually-hidden spinner-border spinner-border-sm text-white" role="status"></span>
								</button>
							</div>
						</div>
					</div>
				)
			}
		}

		ReactDOM.render(<Edit />,  $('#root')[0]);
		
	</script>

</html>
		
		
