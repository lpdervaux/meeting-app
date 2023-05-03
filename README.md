# Meeting App

If `dev` is broken, `main` should be stable. If `main` is broken, yell at us.

## Fixtures

Run `doctrine:fixtures:load` to load dev fixtures.

### Users

Users include 2 default accounts:
* `user@example.org`, a regular user account
* `administrator@example.org`, an administrator account

Every generated account's password is the username part of its email, which is also its nickname.

### Meetups

Meetups include:
* 5 of each:
  * `MeetupStatus::Scheduled`
  * `MeetupStatus::Open`
  * `MeetupStatus::Closed`
  * `MeetupStatus::Ongoing`
  * `MeetupStatus::Concluded`
* 3 of each:
  * `MeetupStatus::Open` at full capacity
  * cancelled while it would otherwise be open
  * cancelled while it would otherwise be closed

Status is relative to the time of generation.

