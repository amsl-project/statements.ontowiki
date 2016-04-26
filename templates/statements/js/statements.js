/**
 * This file is part of the {@link http://amsl.technology amsl} project.
 *
 * @author Sebastian Nuck
 * @copyright Copyright (c) 2015, {@link http://ub.uni-leipzig.de Leipzig University Library}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
var self = this;
var restorebutton;
var expandButton;
var collapsButton;
var lastState = 0;
var source = null;
function addStatement01(node){
    var title = node.title;
    var expanded = node.isExpanded();
    var pos = title.indexOf("<br> <button");
    node.get
    if(!expanded){
        if(pos > -1){
            node.setTitle(title.substr(0, pos - 1).replace('<b>', '').replace('</b>',''));
        }
    }else{
        if(pos == -1){
            node.setTitle('<b>' + node.title + '</b> <br> <button name="abc" class="checkThemAll statementsMyButton">check all</button><button name="abc" class="unCheckThemAll statementsMyButton">un-check all</button> | <button name="abc" class="evaluateThemAll statementsMyButton">evaluate all</button><button name="abc" class="evaluateNone statementsMyButton">evaluate none</button>');
        }
    }
}

function compareHeadlines(headlineA, headlineB){
    var a = headlineA.split('-')[0].replace('<b>', '');
    var b = headlineB.split('-')[0].replace('<b>', '');
    if(a == b){
        return true;
    }else{
        return false;
    }
}

function extractSource(s){
    var startTag = s.indexOf("<p");
    var endTag = s.indexOf("</p>");
    var start = s.indexOf(">", startTag) + 1;
    var result = s.substr(start, endTag-start);
    return result;
}

function addEventlisteners01(){
    $('.checkThemAll').each(function() {
        $( this ).click(function() {
            if(!confirm("Are you sure?")){
                return;
            }
            self.source = $( this ).parent().find(".asdfjka")[0].innerHTML;
            $.get( urlBase + 'statements/checkall?source=' + self.source, function( data ) {
                toastr.success('Changes saved successfully.', 'Select all collections');
                $("#tree").fancytree("getRootNode").visit(function (node) {
                    if (!node.data.collection) {
                        if (extractSource(node.title) == self.source) {
                            node.visit(function (node) {
                                    node.setSelected(true);
                                });
                        }
                    }
                });
            }).fail(function() {
                alert( "Something went wrong - please contact an administrator!" );
            });
        });
    })

    $('.unCheckThemAll').each(function() {
        $( this ).click(function() {
            if(!confirm("Are you sure?")) {
                return;
            }
            self.source = $( this ).parent().find(".asdfjka")[0].innerHTML;
            $.get( urlBase + 'statements/uncheckall?source=' + source, function( data ) {
                toastr.success('Changes saved successfully.', 'Unselect all collections');
                $("#tree").fancytree("getRootNode").visit(function (node) {
                    if (!node.data.collection) {
                        if (extractSource(node.title) == self.source) {
                            node.visit(function (node) {
                                node.setSelected(false);
                            });
                        }
                    }
                });
            }).fail(function() {
                alert( "Something went wrong - please contact an administrator!" );
            });
        });
    })
    $('.evaluateThemAll').each(function() {
        $( this ).click(function() {
            if(!confirm("Are you sure?")){
                return;
            }
            self.source = $( this ).parent().find(".asdfjka")[0].innerHTML;
            $.get( urlBase + 'statements/evaluateall?source=' + source, function( data ) {
                toastr.success('Changes saved successfully.', 'Evaluate all holding files');
                $("#tree").fancytree("getRootNode").visit(function (node) {
                    if (!node.data.collection) {
                        if (extractSource(node.title) == self.source) {
                            node.visit(function (node) {
                                var id = '#' + node.title.substring(node.title.indexOf('id="aasdf') + 4, node.title.indexOf('"', node.title.indexOf('id="aasdf')+5));
                                var checkbox = $(id);
                                checkbox.attr('checked', true);
                            });
                        }
                    }
                });
            });
        });
    })
    $('.evaluateNone').each(function() {
        $( this ).click(function() {
            if(!confirm("Are you sure?")){
                return;
            }
            self.source = $( this ).parent().find(".asdfjka")[0].innerHTML;
            $.get( urlBase + 'statements/evaluetenone?source=' + source, function( data ) {
                toastr.success('Changes saved successfully.', 'Evaluate no holding files');
                $("#tree").fancytree("getRootNode").visit(function (node) {
                    if (!node.data.collection) {
                        if (extractSource(node.title) == self.source) {
                            node.visit(function (node) {
                                var id = '#' + node.title.substring(node.title.indexOf('id="aasdf') + 4, node.title.indexOf('"', node.title.indexOf('id="aasdf')+5));
                                var checkbox = $(id);
                                checkbox.attr('checked', false);
                            });
                        }
                    }
                });
            });
        });
    })
}

function addExpandEventhandlers(){
    $('#unfold').click(function () {
        var url = urlBase + 'extensions/themes/amsl/images/spinner.gif';
        var test = $('#cccdt')[0];
        try {
            test.removeChild(expandButton);
        }catch (e){}
        var newdiv = document.createElement('img');
        newdiv.setAttribute("src", url);
        newdiv.setAttribute("id", "spin");
        test.appendChild(newdiv);
        lastState = 0;
        $("#tree").fancytree("getRootNode").visit(function (node) {
            if (!node.data.collection) {
                if (!node.isExpanded()) {
                    node.data.open = false;
                    node.setExpanded(true, {noAnimation: true, noEvents: true});
                } else {
                    node.data.open = true;
                    lastState++;
                }
            }
            addStatement01(node);
        });
        test.removeChild(newdiv);
        test.appendChild(collapsButton);
        //addCollapsEventhandlers();
        if(lastState > 0) {
            test.appendChild(restorebutton);
            //addRestoreEventhandlers();
        }
        addEventlisteners01();
    });
}

function addCollapsEventhandlers() {
    $('#fold').click(function () {
        var url = urlBase + 'extensions/themes/amsl/images/spinner.gif';
        var test = $('#cccdt')[0];
        try {
        test.removeChild(collapsButton);
        }catch (e){}
        try {
            test.removeChild(restorebutton);
        }catch (e){}
        test.appendChild(expandButton);
        //addExpandEventhandlers();
        var newdiv = document.createElement('img');
        newdiv.setAttribute("src", url);
        newdiv.setAttribute("id", "spin");
        test.appendChild(newdiv);
        $("#tree").fancytree("getRootNode").visit(function (node) {
            if (!node.data.collection) {
                node.data.open = false;
                if (node.isExpanded()) {
                    node.setExpanded(false, {noAnimation: true, noEvents: true});
                }
            }
            addStatement01(node);
        });
        lastState = 0;
        test.removeChild(newdiv);
        addEventlisteners01();
    });
}

function addRestoreEventhandlers() {
    $('#restore').click(function () {
        var url = urlBase + 'extensions/themes/amsl/images/spinner.gif';
        var test = $('#cccdt')[0];
        var newdiv = document.createElement('img');
        newdiv.setAttribute("src", url);
        newdiv.setAttribute("id", "spin");
        test.removeChild(restorebutton);
        test.appendChild(expandButton);
        test.appendChild(collapsButton);
        //addCollapsEventhandlers();
        //addExpandEventhandlers();
        test.appendChild(newdiv);
        $("#tree").fancytree("getRootNode").visit(function (node) {
            if (!node.data.collection) {
                if (!node.data.open) {
                    node.setExpanded(false, {noAnimation: true, noEvents: true});
                }
            }
            addStatement01(node);
        });
        test.removeChild(newdiv);
        addEventlisteners01();
    });
}

function fancyTreeStart (id) {
    $(id).fancytree({
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
            var tree = $(id).fancytree("getTree");
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
    }).on("click", ".fancytree-expander", function (event) {
        var node = $.ui.fancytree.getNode(event);
        addStatement01(node);
        addEventlisteners01();
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

    /**
     * reset search button behaviour
     */
    var tree = $(id).fancytree("getTree");
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
    return $(id).fancytree("getTree");
}

$(document).ready(function () {

    restorebutton = $('#restore')[0];
    expandButton = $('#unfold')[0];
    collapsButton = $('#fold')[0];
    addExpandEventhandlers();
    addCollapsEventhandlers();
    addRestoreEventhandlers();
    restorebutton.remove();
    collapsButton.remove();

    toastr.options = {
        "positionClass": "toast-bottom-right"
    };

    var pathname = window.location.pathname;

    // make sure we are in the right controller
    if (pathname.indexOf("/statements/collectiontolibrary") > -1) {
        toastr.info('Loading collections ....');
        fancyTreeStart("#tree");
    }
});