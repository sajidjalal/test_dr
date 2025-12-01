import React, { useState, useEffect } from "react";
import axios from "axios";

const UserSelectionModal = ({ rootUrl, onClose, onChatCreated }) => {
  const [users, setUsers] = useState([]);
  const [singleUsers, setSingleUsers] = useState([]);
  const [selectedUsers, setSelectedUsers] = useState([]);
  const [groupName, setGroupName] = useState("");
  const [isGroup, setIsGroup] = useState(false);

  useEffect(() => {
    const fetchUsers = async () => {
      try {
        const res = await axios.get(`${rootUrl}/users`);
        setUsers(res.data.users);
        setSingleUsers(res.data.single_chat_users);
      } catch (err) {
        console.error(err);
      }
    };
    fetchUsers();
  }, [rootUrl]);

  const toggleUser = (userId) => {
    if (isGroup) {
      if (selectedUsers.includes(userId)) {
        setSelectedUsers(selectedUsers.filter((id) => id !== userId));
      } else {
        setSelectedUsers([...selectedUsers, userId]);
      }
    } else {
      setSelectedUsers([userId]); // only one for single chat
    }
  };

  const createChat = async () => {
    if (!selectedUsers.length || (isGroup && groupName.trim() === "")) return;

    try {
      const payload = isGroup
        ? { is_group: true, name: groupName, participants: selectedUsers }
        : { is_group: false, other_user_id: selectedUsers[0] };

      await axios.post(`${rootUrl}/chats`, payload);
      onChatCreated();
      onClose();
    } catch (err) {
      console.error(err);
      alert("Error creating chat");
    }
  };

  return (
    <div className="modal show fade" style={{ display: "block" }} tabIndex="-1">
      <div className="modal-dialog modal-dialog-centered">
        <div className="modal-content">
          {/* Modal Header */}
          <div className="modal-header">
            <h5 className="modal-title">{isGroup ? "Create Group" : "Start Chat"}</h5>
            <button type="button" className="btn-close" aria-label="Close" onClick={onClose}></button>
          </div>

          {/* Modal Body */}
          <div className="modal-body">
            {/* Group Toggle */}
            <div className="form-check mb-2">
              <input
                type="checkbox"
                className="form-check-input"
                checked={isGroup}
                onChange={(e) => setIsGroup(e.target.checked)}
                id="isGroupCheck"
              />
              <label className="form-check-label" htmlFor="isGroupCheck">
                Create Group
              </label>
            </div>

            {/* Group Name */}
            {isGroup && (
              <input
                type="text"
                placeholder="Group Name"
                className="form-control mb-3"
                value={groupName}
                onChange={(e) => setGroupName(e.target.value)}
              />
            )}

            {/* Users List */}
            <div className="user-list" style={{ maxHeight: "200px", overflowY: "auto" }}>
              {isGroup
                ?
                users.map((user) => (
                  <div key={user.id} className="form-check">
                    <input
                      type="checkbox"
                      className="form-check-input"
                      checked={selectedUsers.includes(user.id)}
                      onChange={() => toggleUser(user.id)}
                    />
                    <label className="form-check-label">{user.name}</label>
                  </div>
                ))
                :
                singleUsers.map((user) => (
                  <div key={user.id} className="form-check">
                    <input
                      type="radio"
                      className="form-check-input"
                      checked={selectedUsers.includes(user.id)}
                      onChange={() => toggleUser(user.id)}
                    />
                    <label className="form-check-label">{user.name}</label>
                  </div>
                ))
              }
              {singleUsers.length === 0 && <p className="text-muted">No users available</p>}
            </div>
          </div>

          {/* Modal Footer */}
          <div className="modal-footer">
            <button type="button" className="btn btn-secondary" onClick={onClose}>
              Cancel
            </button>
            <button
              type="button"
              className="btn btn-primary"
              onClick={createChat}
              disabled={selectedUsers.length === 0 || (isGroup && groupName.trim() === "")}
            >
              {isGroup ? "Create Group" : "Start Chat"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
};

export default UserSelectionModal;
