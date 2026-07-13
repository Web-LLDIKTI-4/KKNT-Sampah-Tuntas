<?php

namespace App\Support;

class ActionButtons
{
    /**
     * Build a standalone "Edit" (modal trigger) action button.
     */
    public static function edit(
        string $editUrl,
        string $editLinkClass = 'btn-action-edit modalButton',
        string $editIcon = 'ri-edit-box-line',
        string $editTitle = 'Edit Data'
    ): string {
        return '<a href="#modalku" data-bs-toggle="modal" class="'.$editLinkClass.'" data-src="'.$editUrl.'" title="'.$editTitle.'"><i class="'.$editIcon.'"></i></a>';
    }

    /**
     * Build a standalone "Delete" action button (no edit action).
     */
    public static function delete(
        $deleteId,
        string $deleteLinkClass = 'btn-action-delete',
        string $deleteIcon = 'ri-delete-bin-3-line'
    ): string {
        return '<a href="javascript:void(0)" id="hapus_'.$deleteId.'" class="'.$deleteLinkClass.'"><i class="'.$deleteIcon.'"></i></a>';
    }

    /**
     * Build a standard "Edit" (modal trigger) + "Delete" action button pair
     * used inside DataTables `action` columns.
     */
    public static function editDelete(
        string $editUrl,
        $deleteId,
        string $editLinkClass = 'btn-action-edit modalButton',
        string $editIcon = 'ri-edit-box-line',
        string $editTitle = 'Edit Data',
        string $deleteLinkClass = 'btn-action-delete',
        string $deleteIcon = 'ri-delete-bin-3-line'
    ): string {
        return '<div class="d-flex">'
            .static::edit($editUrl, $editLinkClass, $editIcon, $editTitle)
            .' '
            .static::delete($deleteId, $deleteLinkClass, $deleteIcon)
            .'</div>';
    }
}

