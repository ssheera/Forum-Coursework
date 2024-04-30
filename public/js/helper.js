function get_segments() {
	const path = window.location;
	const segments = path.pathname.split('/');
	const mapped = {};
	for (let i = segments.length & 1; i < segments.length; i += 2) {
		mapped[segments[i].toLowerCase()] = segments[i + 1];
	}
	return mapped;
}

const uri = get_segments();
