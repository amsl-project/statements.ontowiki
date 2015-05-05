$(document).ready(function () {
    toastr.options = {
        "positionClass": "toast-bottom-right"
    };

    var pathname = window.location.pathname;

    // make sure we are in the right controller
    if (pathname == "/OntoWiki/statements/collectiontolibrary") {
        toastr.info('Loading collections ....');
        $("#tree").fancytree({
            source: {
                url: urlBase + 'statements/metadatasources'
            },
            activeVisible: false, // Make sure, active nodes are visible (expanded).
            checkbox: true, // Show checkboxes.
            debugLevel: 1, // 0:quiet, 1:normal, 2:debug
            focusOnSelect: true, // Set focus when node is checked by a mouse click
            icons: false, // Display node icons.
            quicksearch: true, // Navigate to next node by typing the first letters.
            extensions: ["filter"],
            keyboard: true,
            filter: {
                autoApply: true,
                autoExpand: true,
                mode: "hide"
            },
            // respond to mouse clicks
            click: function (event, data) {
                saveChanges(event, data);
            },
            // respond to keyboard interactions
            keydown: function (event, data) {
                // activate node with space key
                if (event.which === 32) {
                    saveChanges(event, data);
                }
            },
            // display a message after the tree has been initialized
            init: function (event, data) {
                toastr.success('Collections successfully loaded.');
            }
        }).on("click", "span.fancytree-title", function (event) {
            // Add a click handler to all node titles (using event delegation)
            var node = $.ui.fancytree.getNode(event);
            if (node.data.collection) {
                window.open(
                    urlBase + 'view/?r=' + encodeURIComponent(node.data.collection),
                    '_blank' // <- This is what makes it open in a new window.
                );
            }
        });

        /**
         * reset search button behaviour
         */
        var tree = $("#tree").fancytree("getTree");
        $("input[name=search]").keyup(function (e) {
            var n,
                opts = {
                    autoExpand: true
                },
                match = $(this).val();

            if (e && e.which === $.ui.keyCode.ESCAPE || $.trim(match) === "") {
                $("button#btnResetSearch").click();
                return;
            }
            if ($("#regex").is(":checked")) {
                // Pass function to perform match
                n = tree.filterNodes(function (node) {
                    return new RegExp(match, "i").test(node.title);
                }, opts);
            } else {
                // Pass a string to perform case insensitive matching
                n = tree.filterNodes(match, opts);
            }
            $("button#btnResetSearch").attr("disabled", false);
            $("span#matches").text("(" + n + " matches)");
        }).focus();

        $("button#btnResetSearch").click(function (e) {
            $("input[name=search]").val("");
            $("span#matches").text("");
            tree.clearFilter();
        }).attr("disabled", true);


        /**
         * Saves the changes made to a statment (writes/deletes the property)
         * @param event
         * @param data
         */
        var saveChanges = function (event, data) {
            // A node was activated: write the statement:
            var node = data.node;
            var sourceNode = node.getParent();
            var title = node.title;
            var collection = node.data.collection;
            var source = sourceNode.data.sourceUri;

            if (!data.node.isFolder() && (data.targetType === "checkbox") || event.which === 32) {

                var url;

                if (data.node.isSelected()) {
                    url = urlBase + 'statements/deletearticleindexstatement'
                } else {
                    url = urlBase + 'statements/savearticleindexstatement'
                }

                $.ajax({
                    url: url,
                    data: {collection: collection, source: source},
                    dataType: 'json',
                    success: function (data) {
                        toastr.success('Changes saved successfully.', title);
                    },
                    error: function (data) {
                        toastr.error('Changes could not be saved correctly.', title);
                        if (node.isSelected()) {
                            node.setSelected(false);
                        } else {
                            node.setSelected(true);
                        }
                    }
                });
            }
        }

    }
});








































