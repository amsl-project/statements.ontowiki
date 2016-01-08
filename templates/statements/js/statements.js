/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Sebastian Nuck
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

$(document).ready(function () {
    toastr.options = {
        "positionClass": "toast-bottom-right"
    };

    var pathname = window.location.pathname;

    // make sure we are in the right controller
    if (pathname.indexOf("/statements/collectiontolibrary") > -1) {
        toastr.info('Loading collections ....');
        $("#tree").fancytree({
            source: {
                url: urlBase + 'statements/metadatasources' // get the metadata sources from the controller
            },
            activeVisible: false, // Make sure, active nodes are visible (expanded).
            checkbox: true, // Show checkboxes.
            debugLevel: 1, // 0:quiet, 1:normal, 2:debug
            focusOnSelect: true, // Set focus when node is checked by a mouse click
            icons: true, // Display node icons.
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
                var tree = $("#tree").fancytree("getTree");
                tree.visit(function (node) {
                    node.data.icon = false;
                    node.renderTitle();
                });
                tree.visit(function (node) {
                    if (node.children != null) {
                        for (var i = 0; i < node.children.length; i++) {
                            var leaf = node.children[i];
                            var selected = leaf.selected;
                            var hideCheckbox = leaf.hideCheckbox;
                            var name = leaf.title;
                            if (hideCheckbox == true) {
                                if (selected == true) {
                                    leaf.data.icon = urlBase + 'extensions/statements/images/selectedRestricted.png';
                                } else {
                                    leaf.data.icon = urlBase + 'extensions/statements/images/deselectedRestricted.png';
                                }
                            } else {
                                leaf.data.icon = false;
                            }
                            leaf.renderTitle();
                        }
                    }
                });

            }
        }).on("click", ".headline", function (event) {
            // Add a click handler to all node titles (using event delegation)
            var node = $.ui.fancytree.getNode(event);
            if (node.data.collection) {
                window.open(
                    urlBase + 'view/?r=' + encodeURIComponent(node.data.collection),
                    '_blank' // <- This is what makes it open in a new window.
                );
            }
        }).on("click", ".filecheckbox", function (event) {
            // Add behavior to the 'holdings files checkbox'
            var node = $.ui.fancytree.getNode(event);
            var sourceNode = node.getParent();
            var container = document.createElement('div');
            container.innerHTML = node.title;
            var title = $('.headline', container).text();

            var collection = node.data.collection;
            var source = sourceNode.data.sourceUri;
            var checked = $(this).is(':checked');
            var url = urlBase + 'statements/checkholdingsfiles';

            $.ajax({
                url: url,
                data: {collection: collection, source: source, checked: checked},
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

        });
        $('#unfold').click(function() {
            var url = urlBase + 'extensions/themes/amsl/images/spinner.gif';
            var test = $('#cccdt')[0];
            var newdiv = document.createElement('img');
            newdiv.setAttribute("src", url);
            newdiv.setAttribute("id", "spin");
            test.appendChild(newdiv);
            $("#tree").fancytree("getRootNode").visit(function(node){
                node.setExpanded(true);
            });
            test.removeChild(newdiv);
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
         */
        var saveChanges = function (event, data) {
            // A node was activated: write the statement:
            var node = data.node;
            var sourceNode = node.getParent();
            //var title = node.title;
            var container = document.createElement('div');
            container.innerHTML = node.title;
            var title = $('.headline', container).text();

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