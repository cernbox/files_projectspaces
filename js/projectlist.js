(function() {

	var FileList = function($el, options) {
		this.initialize($el, options);
	};
	FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
		/** @lends OCA.Sharing.FileList.prototype */ {
		appName: 'Project Spaces',
		isPersonal: false,

		/**
		 * @private
		 */
		initialize: function($el, options) {
			if (this.initialized) {
				return;
			}
			
			OCA.Files.FileList.prototype.initialize.apply(this, arguments);
			
			OC.Plugins.attach('OCA.ProjectSpaces.FileList', this);
			
			this.isPersonal = options.personalPage;
			
			this.initialized = true;
		},
		
		_createRow: function(fileData) 
		{
			var $tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
			
			return $tr;
		},
		
		getListAJAXUrl: function()
		{
			if(this.isPersonal)
			{
				return OC.filePath('files_projectspaces', 'ajax', 'personal_list.php');
			}
			else
			{
				return OC.filePath('files_projectspaces', 'ajax', 'list.php');
			}
		},

		reload: function() {
			this._selectedFiles = {};
			this._selectionSummary.clear();
			if (this._currentFileModel) {
				this._currentFileModel.off();
			}
			this._currentFileModel = null;
			this.$el.find('.select-all').prop('checked', false);
			this.showMask();
			if (this._reloadCall) {
				this._reloadCall.abort();
			}
			this._reloadCall = $.ajax({
				url: this.getListAJAXUrl(),
				data: {
					dir : this.getCurrentDirectory(),
					sort: this._sort,
					sortdirection: this._sortDirection
				}
			});
			if (this._detailsView) {
				// close sidebar
				this._updateDetailsView(null);
			}
			var callBack = this.reloadCallback.bind(this);
			return this._reloadCall.then(callBack, callBack);
		},
		
		reloadCallback: function(result) {
			delete this._reloadCall;
			this.hideMask();

			if (!result || result.status === 'error') {
				// if the error is not related to folder we're trying to load, reload the page to handle logout etc
				if (result.data.error === 'authentication_error' ||
					result.data.error === 'token_expired' ||
					result.data.error === 'application_not_enabled'
				) {
					OC.redirect(OC.generateUrl('apps/files'));
				}
				OC.dialogs.alert(result.data.message, 'Error');
				return false;
			}

			// Firewall Blocked request?
			if (result.status === 403) {
				// Go home
				this.changeDirectory('/');
				OC.Notification.showTemporary(t('files', 'This operation is forbidden'));
				return false;
			}

			// Did share service die or something else fail?
			if (result.status === 500) {
				// Go home
				this.changeDirectory('/');
				OC.Notification.showTemporary(t('files', 'This directory is unavailable, please check the logs or contact the administrator'));
				return false;
			}

			if (result.status === 404) {
				// go back home
				this.changeDirectory('/');
				return false;
			}
			// aborted ?
			if (result.status === 0){
				return true;
			}

			if (result.data.permissions) {
				this.setDirectoryPermissions(result.data.permissions);
			}

			this.setFiles(result.data.files);
			return true;
		},
		
		setFiles: function(filesArray) 
        {
                // Remove sharing possibility
                $.each(filesArray, function(index, value)
                {
                        var perm = parseInt(value['permissions']);
                        perm = perm & ~OC.PERMISSION_SHARE;
                        value['permissions'] = perm;
                });
                
                OCA.Files.FileList.prototype.setFiles.apply(this, arguments);
        }
	});


	OCA.ProjectSpaces.FileList = FileList;
})();
