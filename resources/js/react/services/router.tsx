class Routes {
    protected routes : Array<{ doctype: string; doctype_layout?: string; }> = [];
    current_route : Array<[]> = [];

    protected setup(){
        for (let doctype of erp.boot.user.can_read) {
			this.routes[this.slug(doctype)] = { doctype: doctype };
		}
        if (erp.boot.doctype_layouts) {
			for (let doctype_layout of erp.boot.doctype_layouts) {
				this.routes[this.slug(doctype_layout.name)] = {
					doctype: doctype_layout.document_type,
					doctype_layout: doctype_layout.name,
				};
			}
		}
    }

    set_route() {
		// resolve the route from the URL or hash
		// translate it so the objects are well defined
		// and render the page as required
        this.setup()

        return this.routes
	}

    protected slug(name : any) {
		return name.toLowerCase().replace(/ /g, "-");
	}
}  

export default new Routes()