import { Component } from '@angular/core';
import { FormBuilder, Validators } from '@angular/forms';

@Component({
  selector: 'app-create-page',
  templateUrl: './create-page.component.html',
  styleUrls: ['./create-page.component.css']
})
export class CreatePageComponent {
  createPage = this.fb.group({
    title: [null, Validators.required],
    shortTitle: [null, Validators.required],
    slug: [null, Validators.required],
    status: [null, Validators.required]
  });

  statuses = [
    { value: "concept", label: "Concept" },
    { value: "published", label: "Published" },
  ];

  constructor(private fb: FormBuilder) {}

  onSubmit(): void {
    alert('Thanks!');
  }
}
