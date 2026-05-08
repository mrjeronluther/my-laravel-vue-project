<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

class ContactController extends Controller
{
    public function index()
    {
        return Inertia::render('Contacts/Index', [
            'contacts' => Contact::orderBy('name', 'asc')->get()
        ]);
    }

    /**
     * Create Method with Transaction & Concurrency Protection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'min:2', 'max:255', 'unique:contacts', 'regex:/^[a-zA-Z\s]+$/'],
            'phone' => ['required', 'numeric', 'digits:11', 'unique:contacts'],
            'email' => ['nullable', 'email', 'max:255', 'unique:contacts'],
        ]);

        try {
            // Start the SQL Transaction
            return DB::transaction(function () use ($validated) {
                
                // PERFORMANCE NOTE: The 'unique' validation above handles most cases.
                // The Transaction ensures that if 'Contact::create' triggers a DB-level 
                // UniqueConstraintViolation, the whole request is safely rolled back.
                Contact::create($validated);

                return Redirect::back()->with('success', 'Contact successfully committed to host.');
            });

        } catch (Throwable $e) {
            // If two users hit 'Save' at the exact same microsecond, 
            // the Database Unique Constraint will throw an error here.
            return Redirect::back()->withErrors([
                'name' => 'A synchronization conflict occurred. This record may already exist.'
            ]);
        }
    }

    /**
     * Update Method with Pessimistic Locking
     */
    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'name'  => [
                'required', 'string', 'min:2', 'max:255', 
                'regex:/^[a-zA-Z\s]+$/', 
                Rule::unique('contacts')->ignore($contact->id)
            ],
            'phone' => [
                'required', 'numeric', 'digits:11', 
                Rule::unique('contacts')->ignore($contact->id)
            ],
            'email' => [
                'nullable', 'email', 'max:255', 
                Rule::unique('contacts')->ignore($contact->id)
            ],
        ]);

        try {
            return DB::transaction(function () use ($validated, $contact) {
                
                // PESSIMISTIC LOCK: 'lockForUpdate' tells the DB "I am updating this row, 
                // lock it so no other process can modify it until I'm done."
                $lockedContact = Contact::where('id', $contact->id)->lockForUpdate()->first();

                $lockedContact->update($validated);

                return Redirect::back()->with('success', 'Entity synchronization successful.');
            });

        } catch (Throwable $e) {
            return Redirect::back()->withErrors(['error' => 'System busy or update conflict. Please try again.']);
        }
    }

    public function destroy(Contact $contact)
    {
        try {
            DB::transaction(function () use ($contact) {
                $contact->delete();
            });
            return Redirect::back()->with('alert', 'System record purged successfully.');
        } catch (Throwable $e) {
            return Redirect::back()->withErrors(['error' => 'Failed to delete record.']);
        }
    }
}