/// <reference path="../../../js/knockout.d.ts" />
/// <reference path="../../../js/jquery.d.ts" />
/// <reference path="../../../js/jqueryui.d.ts" />
/// <reference path="../../../js/jquery.form.d.ts" />
/// <reference path="../../../js/lodash-3.10.d.ts" />
/// <reference path="./dashboard-widget.ts" />
/// <reference path="../../../modules/actor-selector/actor-selector.ts" />

declare var wsWidgetEditorData: any;
declare var ameWidgetEditor: AmeDashboardWidgetEditor;

interface WidgetEditorSettings {
	format: {
		name: string,
		version: string
	},
	widgets: Array<WidgetPropertyMap>;
	siteComponentHash: string;
}

class AmeDashboardWidgetEditor {
	private static _ = wsAmeLodash;
	private static autoCleanupEnabled: boolean = true;

	widgets: KnockoutObservableArray<AmeDashboardWidget>;

	actorSelector: AmeActorSelector;
	selectedActor: KnockoutComputed<string>;

	public widgetData: KnockoutObservable<string>;
	public widgetDataLength: KnockoutObservable<number>;

	public isExportButtonEnabled: KnockoutObservable<boolean>;

	private initialWidgetSettings: WidgetEditorSettings;
	private isMultisite: boolean = false;

	private static customIdPrefix = 'ame-custom-html-widget-';
	private newWidgetCounter = 0;

	private importDialog: JQuery;
	public importState: KnockoutObservable<string>;
	public uploadButtonEnabled: KnockoutObservable<boolean>;

	public importErrorMessage: KnockoutObservable<string>;
	public importErrorHttpCode: KnockoutObservable<number>;
	public importErrorResponse: KnockoutObservable<string>;


	constructor(widgetSettings: WidgetEditorSettings, selectedActor: string = null, isMultisite: boolean = false) {
		this.isMultisite = isMultisite;

		this.actorSelector = new AmeActorSelector(AmeActors, true);

		//Wrap the selected actor in a computed observable so that it can be used with Knockout.
		var _selectedActor = ko.observable(this.actorSelector.selectedActor);
		this.selectedActor = ko.computed<string>({
			read: function () {
				return _selectedActor();
			},
			write: (newActor: string) => {
				this.actorSelector.setSelectedActor(newActor);
			}
		});
		this.actorSelector.onChange((newSelectedActor: string) => {
			_selectedActor(newSelectedActor);
		});

		//Re-select the previously selected actor, or select "All" (null) by default.
		this.selectedActor(selectedActor);

		this.widgets = ko.observableArray([]);
		this.loadSettings(widgetSettings);

		//These are only updated when saving or exporting widget settings.
		this.widgetData = ko.observable('');
		this.widgetDataLength = ko.observable(0);
		this.isExportButtonEnabled = ko.observable(true);

		//Similarly, these are used when importing settings.
		this.importState = ko.observable('start');
		this.uploadButtonEnabled = ko.observable(false);

		this.importErrorHttpCode = ko.observable(0);
		this.importErrorMessage = ko.observable('');
		this.importErrorResponse = ko.observable('');

		this.setupImportDialog();
	}

	loadSettings(widgetSettings: WidgetEditorSettings) {
		const _ = AmeDashboardWidgetEditor._;
		this.widgets.removeAll();

		for (var i = 0; i < widgetSettings.widgets.length; i++) {
			let properties = widgetSettings.widgets[i],
				widget = null;

			if (properties.hasOwnProperty('wrappedWidget')) {
				widget = new AmeStandardWidgetWrapper(properties, this);
			} else if (_.get(properties, 'widgetType') === 'custom-html') {
				widget = new AmeCustomHtmlWidget(properties, this);
			} else {
				throw {message: 'Unknown widget type', widgetProperties: properties};
			}

			//On a normal site we don't have to worry about plugins that are active on some sites but not others,
			//so we can just remove/filter out widgets that are not present. Just to be safe, however, these changes
			//won't be saved unless the user saves the filtered widget list.
			if (!this.isMultisite && !widget.isPresent && AmeDashboardWidgetEditor.autoCleanupEnabled) {
				continue;
			}

			this.widgets.push(widget);

			//The custom ID counter should be high enough not to clash with existing widgets.
			if (widget.id.indexOf(AmeDashboardWidgetEditor.customIdPrefix) === 0) {
				let idNum = parseInt(widget.id.substr(AmeDashboardWidgetEditor.customIdPrefix.length), 10);
				if (!isNaN(idNum)) {
					this.newWidgetCounter = Math.max(idNum, this.newWidgetCounter);
				}
			}
		}

		this.initialWidgetSettings = widgetSettings;
	}

	removeWidget(widget: AmeDashboardWidget, event) {
		jQuery(event.target).closest('.ame-dashboard-widget').slideUp(300, () => {
			this.widgets.remove(widget);
		});
	}

	addHtmlWidget() {
		this.newWidgetCounter++;

		var widget = new AmeCustomHtmlWidget({
			id: AmeDashboardWidgetEditor.customIdPrefix + this.newWidgetCounter,
			title: 'New Widget ' + this.newWidgetCounter
		}, this);

		//Expand the new widget.
		widget.isOpen(true);

		this.widgets.unshift(widget);
	}

	saveChanges() {
		let settings = this.getCurrentSettings();

		//Set the hidden form fields.
		this.widgetData(jQuery.toJSON(settings));
		this.widgetDataLength(this.widgetData().length);

		//Submit the form.
		return true;
	}

	protected getCurrentSettings(): WidgetEditorSettings {
		const collectionFormatName = 'Admin Menu Editor dashboard widgets';
		const collectionFormatVersion = '1.0';

		var settings: WidgetEditorSettings = {
			format: {
				name: collectionFormatName,
				version: collectionFormatVersion
			},
			widgets: [],
			siteComponentHash: this.initialWidgetSettings.siteComponentHash
		};
		AmeDashboardWidgetEditor._.forEach(this.widgets(), function (widget) {
			settings.widgets.push(widget.toPropertyMap());
		});

		return settings;
	}

	exportWidgets() {
		//Temporarily disable the export button to prevent accidental repeated clicks.
		this.isExportButtonEnabled(false);

		this.widgetData(jQuery.toJSON(this.getCurrentSettings()));

		//Re-enable the export button after a few seconds.
		window.setTimeout(() => {
			this.isExportButtonEnabled(true);
		}, 3000);

		//Explicitly allow form submission.
		return true;
	}

	setupImportDialog() {
		//Note to self: Refactor this as a separate view-model, perhaps.

		this.importDialog = jQuery('#ame-import-widgets-dialog');
		let importForm = this.importDialog.find('#ame-import-widgets-form');

		this.importDialog.dialog({
			autoOpen: false,
			modal: true,
			closeText: ' ',
			open: () => {
				importForm.resetForm();
				this.importState('start');
				this.uploadButtonEnabled(false);
			}
		});

		//jQuery moves the dialog to the end of the DOM tree, which puts it outside our KO root node.
		//This means we must apply bindings directly to the dialog node.
		ko.applyBindings(this, this.importDialog.get(0));

		//Enable the upload button only when the user selects a file.
		importForm.find('#ame-import-file-selector').change((event) => {
			this.uploadButtonEnabled( !!jQuery(event.target).val() );
		});

		//This function displays unhandled server side errors. In theory, our upload handler always returns a well-formed
		//response even if there's an error. In practice, stuff can go wrong in unexpected ways (e.g. plugin conflicts).
		let handleUnexpectedImportError = (xhr, errorMessage) => {
			//The server-side code didn't catch this error, so it's probably something serious
			//and retrying won't work.
			importForm.resetForm();
			this.importState('unexpected-error');

			//Display error information.
			this.importErrorMessage(errorMessage);
			this.importErrorHttpCode(xhr.status);
			this.importErrorResponse((xhr.responseText !== '') ? xhr.responseText : '[Empty response]');
		};

		importForm.ajaxForm({
			dataType : 'json',
			beforeSubmit: (formData) => {

				//Check if the user has selected a file
				for (var i = 0; i < formData.length; i++) {
					if ( formData[i].name === 'widget_file' ){
						if ( (typeof formData[i].value === 'undefined') || !formData[i].value){
							alert('Select a file first!');
							return false;
						}
					}
				}

				this.importState('uploading');
				this.uploadButtonEnabled(false);
				return true;
			},
			success: (data, status, xhr) => {
				if (!this.importDialog.dialog('isOpen')){
					//Whoops, the user closed the dialog while the upload was in progress.
					//Discard the response silently.
					return;
				}

				if ((data === null) || (typeof data !== 'object')) {
					handleUnexpectedImportError(xhr, 'Invalid response from server. Please check your PHP error log.');
					return;
				}

				if (typeof data.error !== 'undefined'){
					alert(data.error.message || data.error.code);
					//Let the user try again.
					importForm.resetForm();
					this.importState('start');
				}

				if ((typeof data.widgets !== 'undefined') && data.widgets) {
					//Lets load these widgets into the editor.
					this.loadSettings(data);

					//Display a success message, then automatically close the window after a few moments.
					this.importState('complete');
					setTimeout(() => {
						this.importDialog.dialog('close');
					}, 700);
				}

			},
			error: function(xhr, status, errorMessage) {
				handleUnexpectedImportError(xhr, errorMessage);
			}
		});

		this.importDialog.find('#ame-cancel-widget-import').click(() => {
			this.importDialog.dialog('close');
		});
	}

	openImportDialog() {
		this.importDialog.dialog('open');
	}
}

//A one-way binding for indeterminate checkbox states.
ko.bindingHandlers['indeterminate'] = {
	update: function (element, valueAccessor) {
		element.indeterminate = !!(ko.unwrap(valueAccessor()));
	}
};

jQuery(function () {
	ameWidgetEditor = new AmeDashboardWidgetEditor(
		wsWidgetEditorData.widgetSettings,
		wsWidgetEditorData.selectedActor,
		wsWidgetEditorData.isMultisite
	);
	ko.applyBindings(ameWidgetEditor, document.getElementById('ame-dashboard-widget-editor'));
});