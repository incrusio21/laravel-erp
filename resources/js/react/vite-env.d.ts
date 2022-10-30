declare var erp: {
    boot : {
        app_logo_url: string
        prefix: {
            api: string
            web: string
        },
        user: {
            can_read: Array<string>
        },
        doctype_layouts : Array<{ name?: string; document_type: string}>
    },
    get_route: Function
    doctype: Object
};