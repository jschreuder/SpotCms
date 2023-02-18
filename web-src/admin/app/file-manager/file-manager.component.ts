import { Component } from '@angular/core';

@Component({
    selector: 'app-file-manager',
    templateUrl: './file-manager.component.html',
    styleUrls: ['./file-manager.component.css']
})
export class FileManagerComponent {
    path = [
        'Home',
        'User',
    ];
    folders = [
        {
            'name': 'Subdirectory',
            'updated': '2004-02-16T15:19:21+00:00',
        },
    ];
    files = [
        {
            'name': 'notes.docx',
            'updated': '2018-09-16T06:19:21+00:00',
        },
        {
            'name': 'profilepicture.png',
            'updated': '2019-02-01T16:28:00+00:00',
        },
    ];
}