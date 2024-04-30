<!DOCTYPE html>
<html lang="en">
	<?php $this->load->view('head', ['title' => 'Create a Post']); ?>
	<body>
		<?php $this->load->view('nav', ['page' => 'create']); ?>
		<div id="root"></div>
	</body>

	<script type="text/babel">
		
		class Create extends React.Component {

			constructor() {
				super();
				this.state = {
					categories: [],
					attachments: []
				}
			}

			async componentDidMount() {

				const localStorage = window.localStorage;
				const token = localStorage.getItem('token');

				if (!token) {
					window.location.href = '<?= base_url('/auth/login') ?>';
					return;
				}

				const categories = [];

				await $.ajax({
					url: '<?= base_url('/posts/categories') ?>',
					type: 'GET',
					headers: {
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
							localStorage.removeItem('token');
							window.location.href = '<?= base_url('/auth/login') ?>';
						}
					}
				});

				this.setState({ categories: categories });
			}
			
			render() {

				const uri_parent = uri['parent'];
				const uri_category = uri['category'];
				const reply = uri_parent || uri_category;

				function formatMemory(bytes) {
					const units = ['B', 'KB', 'MB', 'GB', 'TB'];
					let unitIndex = 0;
					while (bytes >= 1024 && unitIndex < units.length - 1) {
						bytes /= 1024;
						unitIndex++;
					}
					return bytes.toFixed(2) + ' ' + units[unitIndex];
				}

				function handleInput(event) {
					const target = $(event.target);
					const ele = target[0];
					target.css('height', 'auto');
					target.height(ele.scrollHeight - 16);
					$('#attachments').css('max-height', `${ele.scrollHeight + (reply ? 94 : 258)}px`);
				}

				async function handleUpload(event) {
					const target = $(event.target);
					let file = target.prop('files')[0];

					target.val('');

					if (file.size > 1024 * 1024 * 8) {
						alert('File size exceeds 8 MB');
						return;
					}

					const attachment = {
						id: this.state.attachments.length,
						name: file.name,
						data: null,
						size: file.size,
						status: false
					};

					this.setState({ attachments: [...this.state.attachments, attachment] });

					file.data = await file.arrayBuffer();

					attachment.data = file.data;
					attachment.status = true;

					removeAttachment.bind(this)(attachment);

					this.setState({ attachments: [...this.state.attachments, attachment]});
				}

				function removeAttachment(attachment) {
					const index = this.state.attachments.indexOf(attachment);
					if (index > -1) {
						this.state.attachments.splice(index, 1);
					}
					this.setState({ attachments: this.state.attachments });
				}

				async function handleSubmit() {
					const localStorage = window.localStorage;
					const token = localStorage.getItem('token');
					const category = $('#category').val();
					const tags = $('#tags').val();
					const keywords = $('#keywords').val();
					const title = $('#title').val();
					const content = $('#content').val();
					const attachments = this.state.attachments;

					$('#idle-submit').addClass('visually-hidden');
					$('#running-submit').removeClass('visually-hidden');

					let data = {
						category: category,
						tags: tags,
						keywords: keywords,
						title: title,
						content: content
					};

					if (uri_parent) {
						data.parent = uri_parent;
					}

					if (uri_category) {
						data.category = uri_category;
					}

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

								const postId = response.id;

								attachments.map(async attachment => {
									const buffer = new Uint8Array(attachment.data);
									const hex = Array.from(buffer).map(function(byte) {
										return ('0' + (byte & 0xFF).toString(16)).slice(-2);
									}).join('');
									const base64 = btoa(hex);

									await $.ajax({
										url: '<?= base_url('/posts/attach') ?>',
										type: 'POST',
										headers: {
											'X-Token': token
										},
										data: {
											post: postId,
											name: attachment.name,
											size: attachment.size,
											data: base64
										},
									});

								});

								$('#idle-submit').removeClass('visually-hidden');
								$('#running-submit').addClass('visually-hidden');

								if (uri_parent) {
									window.location.href = '<?= base_url('/posts/view/') ?>' + uri_parent;
								} else {
									window.location.href = '<?= base_url('/posts/view/') ?>' + postId;
								}
							} else {
								alert(response.message);
								$('#idle-submit').removeClass('visually-hidden');
								$('#running-submit').addClass('visually-hidden');
							}
						},
						error: function(response) {
							if (response.status === 401) {
								localStorage.removeItem('token');
								window.location.href = '<?= base_url('/auth/login') ?>';
							} else {
								alert('An error occurred');
								$('#idle-submit').removeClass('visually-hidden');
								$('#running-submit').addClass('visually-hidden');
							}
						}
					});
				}

				return (
					<div className="container mt-5 mb-5">
						<div role="form" className="card mt-5 mx-5 rounded-4 shadow border-0">
							<input type="file" id="upload" className="visually-hidden" onInput={handleUpload.bind(this)}/>
							<div className="card-header bg-white border-bottom-0 rounded-4">
								<h4 className="card-title text-secondary mx-2 mt-3 fw-bold">Create a Post</h4>
							</div>
							<div className="card-body mx-2">
								<div className="d-flex flex-row">
									<div className="col-9">
										<div className="me-4">
											{!reply &&
												<div>
													<div className="d-flex flex-row">
														<div className="col-6">
															<div className="form-floating mb-4 me-2">
																<select id="category" className="form-select" aria-label="Select post category" required>
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
															<div className="form-floating mb-4 me-2">
																<input id="tags" className="form-control" aria-label="Tags" placeholder="Tags" />
																<label htmlFor="tags" className="form-label text-secondary">Tags</label>
															</div>
														</div>
														<div className="col-6">
															<div className="form-floating mb-4 ms-2">
																<input id="keywords" className="form-control" aria-label="Keywords" placeholder="Keywords" />
																<label htmlFor="keywords" className="form-label text-secondary">Keywords</label>
															</div>
														</div>
													</div>
												</div>
											}
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
											<div id="attachments" className="d-flex flex-column overflow-auto p-3 pt-1 gap-3" style={{ minHeight: reply ? 132 : 296, maxHeight: reply ? 132 : 296 }}>
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
																	{
																		!attachment.status ?
																			<div className="spinner-border spinner-border-sm text-theme m-2" role="status"></div> :
																			<button className="btn border-0" onClick={() => removeAttachment.bind(this)(attachment)}>
																				<i className="fa-solid fa-xmark fa-2xs bg-transparent"></i>
																			</button>
																	}
																</div>
															</div>
														</div>
													</div>
												))}
											</div>
										</div>
									</div>
								</div>
								<button id="upload" className="btn btn-theme me-2" onClick={() => $('#upload').click()}>Upload Attachment</button>
								<button id="submit" className="btn btn-theme" onClick={handleSubmit.bind(this)}>
									<span id="idle-submit">Create</span>
									<span id="running-submit" className="visually-hidden spinner-border spinner-border-sm text-white" role="status"></span>
								</button>
							</div>
						</div>
					</div>
				)
			}
		}

		ReactDOM.render(<Create />, document.getElementById('root'));
		
	</script>

</html>
		
		
