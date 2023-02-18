import { Component } from '@angular/core';

@Component({
    selector: 'app-site-content',
    templateUrl: './site-content.component.html',
    styleUrls: ['./site-content.component.css']
})
export class SiteContentComponent {
    pages = [
        {
            title: "My first page",
            slug: "homepage",
            children: 0,
        },
        {
            title: "Magic secundo",
            slug: "seconds",
            children: 2,
        },
        {
            title: "The great and fantastical third content",
            slug: "great-third",
            children: 25,
        },
        {
            title: "The emptyness of four",
            slug: "empty",
            children: 0,
        },
        {
            title: "Five will be awesome",
            slug: "awesome",
            children: 3,
        },
    ];
}
